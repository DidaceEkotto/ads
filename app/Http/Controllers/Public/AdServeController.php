<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAdImpressionJob;
use App\Models\AdZone;
use App\Services\AdSelection\AdSelectorService;
use App\Services\Geo\GeoLocationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;

class AdServeController extends Controller
{
    /**
     * Sert le micro-script JS neutre pour un emplacement (/d/{token}.js).
     */
    public function script(Request $request, string $token, AdSelectorService $selector, GeoLocationService $geo): Response
    {
        $zone = AdZone::query()->with('site')->where('token', $token)->first();

        if (! $zone) {
            return response('', 200, ['Content-Type' => 'application/javascript; charset=UTF-8']);
        }

        $device = $this->detectDevice($request->userAgent());
        $location = $geo->locate($request->ip());
        $creative = $selector->selectForZone($zone, [
            'device' => $device,
            'city' => $location['city'],
            'language' => $zone->site?->language ?? 'fr',
        ]);

        if (! $creative) {
            return response('', 200, ['Content-Type' => 'application/javascript; charset=UTF-8']);
        }

        $visitorHash = $this->visitorHash($request);

        ProcessAdImpressionJob::dispatchSync([
            'campaign_id' => $creative->campaign_id,
            'creative_id' => $creative->id,
            'site_id' => $zone->site_id,
            'ad_zone_id' => $zone->id,
            'publisher_id' => $zone->site->publisher_id,
            'visitor_hash' => $visitorHash,
            'ip_address' => (string) $request->ip(),
            'country' => $location['country'],
            'city' => $location['city'],
            'device_type' => $device,
        ]);

        $clickToken = Crypt::encryptString(json_encode([
            'campaign_id' => $creative->campaign_id,
            'creative_id' => $creative->id,
            'site_id' => $zone->site_id,
            'ad_zone_id' => $zone->id,
            'publisher_id' => $zone->site->publisher_id,
            'destination_url' => $creative->destination_url,
            'exp' => now()->addHours(6)->timestamp,
        ], JSON_THROW_ON_ERROR));

        $clickUrl = route('adserving.click', ['token' => $clickToken]);
        $metricUrl = route('adserving.adblock');
        $viewUrl = route('adserving.view');
        $viewToken = Crypt::encryptString(json_encode([
            'campaign_id' => $creative->campaign_id,
            'publisher_id' => $zone->site->publisher_id,
            'site_id' => $zone->site_id,
            'ad_zone_id' => $zone->id,
            'exp' => now()->addHours(6)->timestamp,
        ], JSON_THROW_ON_ERROR));
        $imgUrl = $creative->mediaUrl();
        $title = e($creative->title);
        $body = e((string) $creative->body_text);
        $slotId = 'cm-rec-'.$zone->token;

        // Script autonome léger, classes CSS neutres, lazy-loading et détection polie de bloqueur
        $html = $zone->is_native
            ? "<a href=\"{$clickUrl}\" target=\"_blank\" rel=\"noopener sponsored\" class=\"cm-rec-card\" style=\"display:flex;gap:10px;align-items:center;text-decoration:none;color:inherit;border:1px solid #e5e7eb;border-radius:8px;padding:8px;max-width:100%;\"><img src=\"{$imgUrl}\" alt=\"{$title}\" loading=\"lazy\" style=\"width:72px;height:72px;object-fit:cover;border-radius:6px;\"><div><div style=\"font-weight:600;font-size:14px;\">{$title}</div><div style=\"font-size:12px;color:#6b7280;\">{$body}</div></div></a>"
            : "<a href=\"{$clickUrl}\" target=\"_blank\" rel=\"noopener sponsored\" class=\"cm-rec-card\" style=\"display:inline-block;max-width:100%;\"><img src=\"{$imgUrl}\" alt=\"{$title}\" loading=\"lazy\" style=\"max-width:100%;height:auto;border:0;border-radius:4px;\"></a>";

        $escapedHtml = json_encode($html, JSON_THROW_ON_ERROR);
        $escapedZone = json_encode($zone->token, JSON_THROW_ON_ERROR);
        $escapedMetric = json_encode($metricUrl, JSON_THROW_ON_ERROR);
        $escapedViewUrl = json_encode($viewUrl, JSON_THROW_ON_ERROR);
        $escapedViewToken = json_encode($viewToken, JSON_THROW_ON_ERROR);

        $js = <<<JS
(function(){
  var el=document.getElementById('{$slotId}');
  if(!el)return;
  el.innerHTML={$escapedHtml};
  try{
    if('IntersectionObserver' in window){
      var sent=false;
      var obs=new IntersectionObserver(function(entries){
        if(!sent && entries[0] && entries[0].isIntersecting){
          sent=true;obs.disconnect();
          if(navigator.sendBeacon){navigator.sendBeacon({$escapedViewUrl},new URLSearchParams({view_token:{$escapedViewToken}}));}
        }
      },{threshold:0.5});
      obs.observe(el);
    }
    var img=el.querySelector('img');
    if(img){
      img.addEventListener('error',function(){
        el.innerHTML='<div style="font-size:12px;padding:8px;border:1px dashed #ccc;border-radius:6px;color:#555;">Soutenez ce site indépendant en désactivant votre bloqueur pour nos partenaires locaux.</div>';
        if(navigator.sendBeacon){navigator.sendBeacon({$escapedMetric},new URLSearchParams({zone_token:{$escapedZone}}));}
      });
    }
  }catch(e){}
})();
JS;

        return response($js, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Variante HTML légère pour intégration en <iframe> (/d/frame/{token}).
     */
    public function frame(Request $request, string $token, AdSelectorService $selector): Response
    {
        $zone = AdZone::query()->with('site')->where('token', $token)->first();
        if (! $zone) {
            return response('', 204);
        }

        $device = $this->detectDevice($request->userAgent());
        $creative = $selector->selectForZone($zone, ['device' => $device]);
        if (! $creative) {
            return response('', 204);
        }

        $clickToken = Crypt::encryptString(json_encode([
            'campaign_id' => $creative->campaign_id,
            'creative_id' => $creative->id,
            'site_id' => $zone->site_id,
            'ad_zone_id' => $zone->id,
            'publisher_id' => $zone->site->publisher_id,
            'destination_url' => $creative->destination_url,
            'exp' => now()->addHours(6)->timestamp,
        ], JSON_THROW_ON_ERROR));

        return response()->view('public.embed', [
            'creative' => $creative,
            'clickUrl' => route('adserving.click', ['token' => $clickToken]),
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

    private function visitorHash(Request $request): string
    {
        return hash('sha256', ($request->ip() ?? '').'|'.($request->userAgent() ?? ''));
    }
}
