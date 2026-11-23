<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AdZone;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class AdBlockMetricController extends Controller
{
    /**
     * Enregistre une détection de bloqueur de publicité (sans contournement agressif).
     */
    public function record(Request $request): Response
    {
        $token = (string) $request->input('zone_token', '');
        if ($token !== '') {
            $zone = AdZone::query()->where('token', $token)->first();
            if ($zone) {
                $dateKey = now()->format('Y-m-d');
                Cache::increment("rt_adblock:{$dateKey}:s:{$zone->site_id}");
            }
        }

        return response()->noContent();
    }
}
