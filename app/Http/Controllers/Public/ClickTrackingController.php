<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAdClickJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class ClickTrackingController extends Controller
{
    /**
     * Enregistre le clic (avec contrôle anti-fraude) et redirige vers l'annonceur.
     */
    public function track(Request $request, string $token): RedirectResponse
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            abort(404);
        }

        $destinationUrl = $payload['destination_url'] ?? null;
        if (! $destinationUrl || ! filter_var($destinationUrl, FILTER_VALIDATE_URL)) {
            abort(404);
        }

        $ua = $request->userAgent();
        $ip = (string) $request->ip();
        $visitorHash = hash('sha256', $ip.'|'.($ua ?? ''));

        $device = str_contains(strtolower((string) $ua), 'mobile') ? 'mobile' : 'desktop';

        ProcessAdClickJob::dispatch([
            'campaign_id' => (int) $payload['campaign_id'],
            'creative_id' => isset($payload['creative_id']) ? (int) $payload['creative_id'] : null,
            'site_id' => isset($payload['site_id']) ? (int) $payload['site_id'] : null,
            'ad_zone_id' => isset($payload['ad_zone_id']) ? (int) $payload['ad_zone_id'] : null,
            'social_placement_id' => null,
            'tracking_link_id' => null,
            'publisher_id' => (int) $payload['publisher_id'],
            'visitor_hash' => $visitorHash,
            'ip_address' => $ip,
            'user_agent' => $ua,
            'referer' => $request->headers->get('referer'),
            'city' => null,
            'device_type' => $device,
            'authenticated_user_id' => $request->user()?->id,
        ]);

        return redirect()->away($destinationUrl);
    }
}
