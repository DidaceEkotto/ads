<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Click;
use App\Models\Conversion;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ConversionPixelController extends Controller
{
    /**
     * Pixel de conversion CPA 1x1 transparent (GET /m/conv/{campaign}).
     */
    public function track(Request $request, int $campaign): Response
    {
        $visitorHash = hash('sha256', ($request->ip() ?? '').'|'.($request->userAgent() ?? ''));

        $click = Click::query()
            ->where('campaign_id', $campaign)
            ->where('visitor_hash', $visitorHash)
            ->where('is_valid', true)
            ->where('created_at', '>=', now()->subDays(30))
            ->latest('created_at')
            ->first();

        if ($click) {
            Conversion::query()->create([
                'campaign_id' => $campaign,
                'click_id' => $click->id,
                'visitor_hash' => $visitorHash,
                'event_name' => mb_substr((string) $request->query('event', 'purchase'), 0, 60),
                'value_xaf' => max(0, (int) $request->query('value', 0)),
                'created_at' => now(),
            ]);
        }

        // Retourne un GIF 1x1 transparent
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($gif, 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
