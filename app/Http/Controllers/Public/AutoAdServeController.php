<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAdImpressionJob;
use App\Models\AdSlotMetric;
use App\Models\Site;
use App\Services\AdSelection\AdSelectorService;
use App\Services\AdSelection\SlotBanditService;
use App\Services\Geo\GeoLocationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;

class AutoAdServeController extends Controller
{
    /**
     * Script unique à placer dans le <head> du site éditeur (/a/{siteToken}.js).
     * Détecte automatiquement les zones adaptées du DOM et y insère les publicités autorisées.
     */
    public function script(
        Request $request,
        string $siteToken,
        AdSelectorService $selector,
        GeoLocationService $geo,
        SlotBanditService $bandit
    ): Response {
        $site = Site::query()->where('site_token', $siteToken)->first();

        if (! $site || ! $site->isApproved() || ! $site->auto_ads_enabled) {
            return response('', 200, ['Content-Type' => 'application/javascript; charset=UTF-8']);
        }

        $formats = $site->allowed_formats ?: ['native', 'square_300x250'];
        $device = $this->detectDevice($request->userAgent());
        $location = $geo->locate($request->ip());
        $visitorHash = hash('sha256', ($request->ip() ?? '').'|'.($request->userAgent() ?? ''));

        $placements = [];
        $recordedCreativeIds = [];
        $usedCreativeIds = [];
        $workingFormats = [];
        $candidateFormats = array_values(array_unique($formats));
        shuffle($candidateFormats);
        $rankedSlots = $bandit->rankSlots($site->id, $device, 4);

        // 1. On remplit d'abord avec les différents formats autorisés qui ont des publicités actives
        foreach (array_slice($candidateFormats, 0, 4) as $format) {
            $creative = $selector->selectForSiteAndFormat($site, $format, [
                'device' => $device,
                'city' => $location['city'],
                'language' => $site->language ?? 'fr',
                'exclude_creative_ids' => $usedCreativeIds,
            ]);

            if (! $creative) {
                continue;
            }

            $workingFormats[] = $format;
            $usedCreativeIds[] = $creative->id;
            $slotKey = $rankedSlots[count($placements)] ?? 'below_navbar';
            $shouldRecord = ! in_array($creative->id, $recordedCreativeIds, true);
            $recordedCreativeIds[] = $creative->id;

            $placements[] = $this->buildPlacement(
                $request,
                $site,
                $creative,
                $format,
                $slotKey,
                $visitorHash,
                $location,
                $device,
                $shouldRecord
            );
        }

        // 2. Garantit minimum 3 publicités par page (jusqu'à 4) en piochant aléatoirement parmi les formats actifs
        if ($workingFormats !== []) {
            $targetCount = max(3, min(4, count($workingFormats) + 2));
            $attempts = 0;
            while (count($placements) < $targetCount && $attempts < 10) {
                $attempts++;
                $fallbackFormat = $workingFormats[array_rand($workingFormats)];
                $creative = $selector->selectForSiteAndFormat($site, $fallbackFormat, [
                    'device' => $device,
                    'city' => $location['city'],
                    'language' => $site->language ?? 'fr',
                    'exclude_creative_ids' => $usedCreativeIds,
                ]);
                if (! $creative) {
                    continue;
                }
                $usedCreativeIds[] = $creative->id;
                $slotKey = $rankedSlots[count($placements)] ?? 'before_footer';
                $shouldRecord = ! in_array($creative->id, $recordedCreativeIds, true);
                $recordedCreativeIds[] = $creative->id;

                $placements[] = $this->buildPlacement(
                    $request,
                    $site,
                    $creative,
                    $fallbackFormat,
                    $slotKey,
                    $visitorHash,
                    $location,
                    $device,
                    $shouldRecord
                );
            }
        }

        if ($placements === []) {
            return response('', 200, ['Content-Type' => 'application/javascript; charset=UTF-8']);
        }

        $jsonPlacements = json_encode($placements, JSON_THROW_ON_ERROR);
        $viewUrl = json_encode(route('adserving.view'), JSON_THROW_ON_ERROR);

        $js = <<<JS
(function(){
  var items={$jsonPlacements};
  var viewUrl={$viewUrl};
  function isInNavbar(el){
    return !!(el && el.closest && el.closest('header, nav, .navbar, form, [role="dialog"]'));
  }
  function tooCloseToExisting(el){
    if(!el||!el.getBoundingClientRect)return false;
    var r=el.getBoundingClientRect();
    var existing=document.querySelectorAll('.cm-auto-slot');
    for(var i=0;i<existing.length;i++){
      var er=existing[i].getBoundingClientRect();
      if(Math.abs(er.top-r.top)<300)return true;
    }
    return false;
  }
  function placeBelowNavbar(box){
    var nav=document.querySelector('header, nav, .navbar');
    if(nav&&nav.parentNode){
      var cs=window.getComputedStyle(nav);
      if(cs.position==='fixed'){
        box.style.marginTop=(nav.offsetHeight+12)+'px';
      }
      nav.insertAdjacentElement('afterend',box);
      return true;
    }
    var main=document.querySelector('main, article');
    if(main&&!isInNavbar(main)){main.prepend(box);return true;}
    if(document.body){document.body.prepend(box);return true;}
    return false;
  }
  function placeByHeuristic(box,slotKey){
    if(slotKey==='below_navbar'){return placeBelowNavbar(box);}
    if(slotKey==='sidebar'){
      var sb=document.querySelector('aside, .sidebar');
      if(sb&&!isInNavbar(sb)&&sb.offsetWidth>=160){sb.appendChild(box);return true;}
    }
    if(slotKey==='after_p2'){
      var paras=Array.prototype.filter.call(document.querySelectorAll('article p, main p, p'),function(el){return !isInNavbar(el)&&(el.textContent||'').trim().length>40;});
      for(var i=1;i<paras.length;i++){
        if(!tooCloseToExisting(paras[i])){paras[i].insertAdjacentElement('afterend',box);return true;}
      }
    }
    if(slotKey==='mid_section'){
      var secs=Array.prototype.filter.call(document.querySelectorAll('main > section, section, article'),function(el){return !isInNavbar(el)&&el.offsetHeight>=120;});
      for(var j=0;j<secs.length;j++){
        if(!tooCloseToExisting(secs[j])){secs[j].insertAdjacentElement('afterend',box);return true;}
      }
    }
    var footer=document.querySelector('footer, .footer');
    if(footer&&footer.parentNode&&!isInNavbar(footer)){footer.insertAdjacentElement('beforebegin',box);return true;}
    var main=document.querySelector('main, article');
    if(main&&!isInNavbar(main)){main.appendChild(box);return true;}
    if(document.body){document.body.appendChild(box);return true;}
    return false;
  }
  function mount(){
    if(document.querySelector('.cm-auto-slot'))return;
    items.forEach(function(item){
      var box=document.createElement('div');
      box.className='cm-auto-slot';
      box.setAttribute('data-cm-slot',item.slot_key||'auto');
      box.style.cssText='position:static;display:block;clear:both;width:100%;margin:12px 0;';
      box.innerHTML=item.html;
      placeByHeuristic(box,item.slot_key||'below_navbar');
      if('IntersectionObserver' in window && box.parentNode){
        var sent=false;
        var obs=new IntersectionObserver(function(e){
          if(!sent && e[0] && e[0].isIntersecting){
            sent=true;obs.disconnect();
            if(navigator.sendBeacon){navigator.sendBeacon(viewUrl,new URLSearchParams({view_token:item.view_token}));}
          }
        },{threshold:0.5});
        obs.observe(box);
      }
    });
  }
  if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',mount);}
  else{mount();}
  setTimeout(mount,700);
  window.addEventListener('popstate',function(){setTimeout(mount,300);});
  window.addEventListener('hashchange',function(){setTimeout(mount,300);});
})();
JS;

        return response($js, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * @param  array{country: string, city: string}  $location
     * @return array{format: string, html: string, view_token: string}
     */
    private function buildPlacement(
        Request $request,
        Site $site,
        \App\Models\Creative $creative,
        string $format,
        string $slotKey,
        string $visitorHash,
        array $location,
        string $device,
        bool $recordImpression = true
    ): array {
        AdSlotMetric::recordImpression($site->id, $slotKey, $device);

        if ($recordImpression) {
            ProcessAdImpressionJob::dispatchSync([
                'campaign_id' => $creative->campaign_id,
                'creative_id' => $creative->id,
                'site_id' => $site->id,
                'ad_zone_id' => null,
                'publisher_id' => $site->publisher_id,
                'visitor_hash' => $visitorHash,
                'ip_address' => (string) $request->ip(),
                'country' => $location['country'],
                'city' => $location['city'],
                'device_type' => $device,
            ]);
        }

        $clickToken = Crypt::encryptString(json_encode([
            'campaign_id' => $creative->campaign_id,
            'creative_id' => $creative->id,
            'site_id' => $site->id,
            'ad_zone_id' => null,
            'slot_key' => $slotKey,
            'publisher_id' => $site->publisher_id,
            'destination_url' => $creative->destination_url,
            'exp' => now()->addHours(6)->timestamp,
        ], JSON_THROW_ON_ERROR));

        $viewToken = Crypt::encryptString(json_encode([
            'campaign_id' => $creative->campaign_id,
            'publisher_id' => $site->publisher_id,
            'site_id' => $site->id,
            'slot_key' => $slotKey,
            'device_type' => $device,
            'exp' => now()->addHours(6)->timestamp,
        ], JSON_THROW_ON_ERROR));

        $clickUrl = route('adserving.click', ['token' => $clickToken]);
        $mediaUrl = $creative->mediaUrl();
        $title = e($creative->title);
        $body = e((string) $creative->body_text);

        // Dimensions strictes selon le format choisi
        $dimensions = match ($format) {
            'banner_728x90' => ['w' => '728px', 'h' => '90px'],
            'square_300x250' => ['w' => '300px', 'h' => '250px'],
            'vertical_160x600' => ['w' => '160px', 'h' => '600px'],
            default => null,
        };

        if ($format === 'native') {
            $thumbTag = $creative->media_type === 'video'
                ? "<video src=\"{$mediaUrl}\" autoplay muted loop playsinline preload=\"metadata\" style=\"width:72px;height:72px;object-fit:cover;border-radius:8px;flex-shrink:0;\"></video>"
                : "<img src=\"{$mediaUrl}\" alt=\"{$title}\" loading=\"lazy\" style=\"width:72px;height:72px;object-fit:cover;border-radius:8px;flex-shrink:0;\">";

            $html = "<a href=\"{$clickUrl}\" target=\"_blank\" rel=\"noopener sponsored\" class=\"cm-rec-card\" style=\"display:flex;gap:12px;align-items:center;max-width:720px;margin:16px auto;text-decoration:none;color:inherit;border:1px solid #e5e7eb;border-radius:10px;padding:10px;background:#fff;box-sizing:border-box;\">{$thumbTag}<div><div style=\"font-weight:600;font-size:14px;\">{$title}</div><div style=\"font-size:12px;color:#6b7280;\">{$body}</div></div></a>";
        } else {
            $w = $dimensions['w'] ?? '300px';
            $h = $dimensions['h'] ?? '250px';
            $mediaStyle = "max-width:100%;width:auto;height:auto;max-height:{$h};object-fit:contain;border:0;border-radius:6px;display:block;margin:0 auto;";
            $mediaTag = $creative->media_type === 'video'
                ? "<video src=\"{$mediaUrl}\" autoplay muted loop playsinline preload=\"metadata\" style=\"{$mediaStyle}\"></video>"
                : "<img src=\"{$mediaUrl}\" alt=\"{$title}\" loading=\"lazy\" style=\"{$mediaStyle}\">";

            $html = "<div class=\"cm-rec-wrap\" style=\"max-width:{$w};width:100%;margin:16px auto;text-align:center;box-sizing:border-box;\"><a href=\"{$clickUrl}\" target=\"_blank\" rel=\"noopener sponsored\" class=\"cm-rec-card\" style=\"display:inline-block;max-width:100%;\">{$mediaTag}</a></div>";
        }

        return [
            'format' => $format,
            'slot_key' => $slotKey,
            'html' => $html,
            'view_token' => $viewToken,
        ];
    }

    private function detectDevice(?string $userAgent): string
    {
        $ua = strtolower((string) $userAgent);
        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
            return 'tablet';
        }
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'mobile';
        }

        return 'desktop';
    }
}
