<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAdClickJob;
use App\Models\TrackingLink;
use App\Services\AntiFraud\BotUserAgentList;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SocialRedirectController extends Controller
{
    /**
     * Gère les liens courts sociaux (/c/{code}) :
     * - Sert les balises OpenGraph aux robots d'aperçu sans facturer
     * - Enregistre le clic et redirige les visiteurs humains (y compris in-app browsers)
     */
    public function redirect(Request $request, string $code): SymfonyResponse
    {
        $link = TrackingLink::query()
            ->with(['campaign.creatives'])
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if (! $link || ! $link->campaign) {
            abort(404);
        }

        $creative = $link->campaign->creatives->first();
        if (! $creative) {
            abort(404);
        }

        $ua = $request->userAgent();

        // Filtrage des robots d'aperçu (Facebookexternalhit, Twitterbot, LinkedInBot...)
        if (BotUserAgentList::isPreviewBot($ua)) {
            return response()->view('public.social_preview', [
                'title' => $creative->title,
                'description' => $creative->body_text ?? $link->campaign->name,
                'imageUrl' => $creative->mediaUrl(),
                'url' => $request->fullUrl(),
            ], Response::HTTP_OK);
        }

        $ip = (string) $request->ip();
        $visitorHash = hash('sha256', $ip.'|'.($ua ?? ''));
        $device = str_contains(strtolower((string) $ua), 'mobile') || str_contains(strtolower((string) $ua), 'fban')
            ? 'mobile'
            : 'desktop';

        ProcessAdClickJob::dispatch([
            'campaign_id' => $link->campaign_id,
            'creative_id' => $creative->id,
            'site_id' => null,
            'ad_zone_id' => null,
            'social_placement_id' => $link->social_placement_id,
            'tracking_link_id' => $link->id,
            'publisher_id' => $link->publisher_id,
            'visitor_hash' => $visitorHash,
            'ip_address' => $ip,
            'user_agent' => $ua,
            'referer' => $request->headers->get('referer'),
            'city' => null,
            'device_type' => $device,
            'authenticated_user_id' => null,
        ]);

        return redirect()->away($creative->destination_url);
    }
}
