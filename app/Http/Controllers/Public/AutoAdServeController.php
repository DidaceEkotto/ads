<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAdImpressionJob;
use App\Models\Site;
use App\Services\AdSelection\AdSelectorService;
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
        GeoLocationService $geo
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

        foreach (array_slice($formats, 0, 3) as $format) {
            $creative = $selector->selectForSiteAndFormat($site, $format, [
                'device' => $device,
                'city' => $location['city'],
                'language' => $site->language ?? 'fr',
            ]);

            if (! $creative) {
                continue;
            }

            ProcessAdImpressionJob::dispatch([
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

            $clickToken = Crypt::encryptString(json_encode([
                'campaign_id' => $creative->campaign_id,
                'creative_id' => $creative->id,
                'site_id' => $site->id,
                'ad_zone_id' => null,
                'publisher_id' => $site->publisher_id,
                'destination_url' => $creative->destination_url,
                'exp' => now()->addHours(6)->timestamp,
            ], JSON_THROW_ON_ERROR));

            $viewToken = Crypt::encryptString(json_encode([
                'campaign_id' => $creative->campaign_id,
                'publisher_id' => $site->publisher_id,
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
                $mediaStyle = "width:100%;height:{$h};object-fit:cover;border:0;border-radius:6px;display:block;";
                $mediaTag = $creative->media_type === 'video'
                    ? "<video src=\"{$mediaUrl}\" autoplay muted loop playsinline preload=\"metadata\" style=\"{$mediaStyle}\"></video>"
                    : "<img src=\"{$mediaUrl}\" alt=\"{$title}\" loading=\"lazy\" style=\"{$mediaStyle}\">";

                $html = "<div class=\"cm-rec-wrap\" style=\"width:{$w};max-width:100%;height:{$h};margin:16px auto;overflow:hidden;border-radius:6px;box-sizing:border-box;\"><a href=\"{$clickUrl}\" target=\"_blank\" rel=\"noopener sponsored\" class=\"cm-rec-card\" style=\"display:block;width:100%;height:100%;\">{$mediaTag}</a></div>";
            }

            $placements[] = [
                'format' => $format,
                'html' => $html,
                'view_token' => $viewToken,
            ];
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
  function mount(){
    items.forEach(function(item){
      var box=document.createElement('div');
      box.className='cm-auto-slot';
      box.style.cssText='position:static;display:block;clear:both;width:100%;margin:12px 0;';
      box.innerHTML=item.html;
      var target=null;
      if(item.format==='banner_728x90'){
        target=document.querySelector('main, article');
        if(target){target.prepend(box);}
        else{
          var hdr=document.querySelector('header, nav');
          if(hdr&&hdr.parentNode){hdr.insertAdjacentElement('afterend',box);}
          else if(document.body){document.body.prepend(box);}
        }
      }else if(item.format==='vertical_160x600'||item.format==='square_300x250'){
        target=document.querySelector('aside, .sidebar, main, article');
        if(target){target.appendChild(box);}
        else if(document.body){document.body.appendChild(box);}
      }else{
        var paras=document.querySelectorAll('article p, main p, p');
        if(paras.length>=2){paras[1].insertAdjacentElement('afterend',box);}
        else if(document.body){document.body.appendChild(box);}
      }
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
})();
JS;

        return response($js, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
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
