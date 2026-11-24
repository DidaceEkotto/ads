<?php

namespace App\Http\Controllers\Publisher;

use App\Http\Controllers\Controller;
use App\Models\AdSlotMetric;
use App\Models\Campaign;
use App\Models\Click;
use App\Models\Impression;
use App\Models\Site;
use App\Models\SocialPlacement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class StatsController extends Controller
{
    /**
     * Statistiques détaillées et revenus en temps réel (requêtes directes sans cron ni jobs).
     */
    public function __invoke(Request $request): InertiaResponse
    {
        $user = $request->user();
        $todayStart = now()->startOfDay();

        $totalImpressions = Impression::query()
            ->where('publisher_id', $user->id)
            ->count();

        $todayImpressions = Impression::query()
            ->where('publisher_id', $user->id)
            ->where('created_at', '>=', $todayStart)
            ->count();

        $uniqueVisitors = Impression::query()
            ->where('publisher_id', $user->id)
            ->distinct('visitor_hash')
            ->count('visitor_hash');

        $clickTotals = Click::query()
            ->where('publisher_id', $user->id)
            ->selectRaw('
                COALESCE(SUM(CASE WHEN is_valid = 1 THEN 1 ELSE 0 END), 0) as valid_clicks,
                COALESCE(SUM(CASE WHEN is_valid = 0 THEN 1 ELSE 0 END), 0) as blocked_clicks,
                COALESCE(SUM(CASE WHEN is_valid = 1 THEN publisher_earning_xaf ELSE 0 END), 0) as total_earnings,
                COALESCE(SUM(CASE WHEN is_valid = 1 AND earning_status = \'pending\' THEN publisher_earning_xaf ELSE 0 END), 0) as pending_earnings,
                COALESCE(SUM(CASE WHEN is_valid = 1 AND earning_status = \'released\' THEN publisher_earning_xaf ELSE 0 END), 0) as released_earnings
            ')
            ->first();

        $todayClickTotals = Click::query()
            ->where('publisher_id', $user->id)
            ->where('created_at', '>=', $todayStart)
            ->selectRaw('
                COALESCE(SUM(CASE WHEN is_valid = 1 THEN 1 ELSE 0 END), 0) as valid_clicks,
                COALESCE(SUM(CASE WHEN is_valid = 1 THEN publisher_earning_xaf ELSE 0 END), 0) as earnings
            ')
            ->first();

        $validClicks = (int) ($clickTotals->valid_clicks ?? 0);
        $blockedClicks = (int) ($clickTotals->blocked_clicks ?? 0);
        $totalEarnings = (int) ($clickTotals->total_earnings ?? 0);
        $ctr = $totalImpressions > 0 ? round(($validClicks / $totalImpressions) * 100, 2) : 0;
        $rpm = $totalImpressions > 0 ? (int) round(($totalEarnings / $totalImpressions) * 1000) : 0;
        $avgCpc = $validClicks > 0 ? (int) round($totalEarnings / $validClicks) : 0;

        $wallet = $user->wallet()->firstOrCreate(['user_id' => $user->id]);

        // Performance en direct par publicité / campagne diffusée
        $impByCampaign = Impression::query()
            ->where('publisher_id', $user->id)
            ->select([
                'campaign_id',
                DB::raw('COUNT(*) as impressions'),
                DB::raw('COUNT(DISTINCT visitor_hash) as unique_visitors'),
            ])
            ->groupBy('campaign_id')
            ->get()
            ->keyBy('campaign_id');

        $clkByCampaign = Click::query()
            ->where('publisher_id', $user->id)
            ->selectRaw('
                campaign_id,
                COALESCE(SUM(CASE WHEN is_valid = 1 THEN 1 ELSE 0 END), 0) as valid_clicks,
                COALESCE(SUM(CASE WHEN is_valid = 0 THEN 1 ELSE 0 END), 0) as blocked_clicks,
                COALESCE(SUM(CASE WHEN is_valid = 1 THEN publisher_earning_xaf ELSE 0 END), 0) as earnings_xaf,
                COALESCE(SUM(CASE WHEN is_valid = 1 AND earning_status = \'pending\' THEN publisher_earning_xaf ELSE 0 END), 0) as pending_xaf,
                COALESCE(SUM(CASE WHEN is_valid = 1 AND earning_status = \'released\' THEN publisher_earning_xaf ELSE 0 END), 0) as released_xaf
            ')
            ->groupBy('campaign_id')
            ->get()
            ->keyBy('campaign_id');

        $campaignIds = $impByCampaign->keys()->merge($clkByCampaign->keys())->unique()->values();
        $campaignsMap = Campaign::query()->whereIn('id', $campaignIds)->get()->keyBy('id');

        $byCampaign = $campaignIds->map(function ($cid) use ($campaignsMap, $impByCampaign, $clkByCampaign) {
            $camp = $campaignsMap->get($cid);
            $imp = $impByCampaign->get($cid);
            $clk = $clkByCampaign->get($cid);
            $views = (int) ($imp->impressions ?? 0);
            $clicks = (int) ($clk->valid_clicks ?? 0);

            return [
                'id' => (int) $cid,
                'name' => $camp?->name ?? "Campagne #{$cid}",
                'billing_model' => $camp ? strtoupper($camp->billing_model->value) : 'CPC',
                'impressions' => $views,
                'unique_visitors' => (int) ($imp->unique_visitors ?? 0),
                'valid_clicks' => $clicks,
                'blocked_clicks' => (int) ($clk->blocked_clicks ?? 0),
                'ctr' => $views > 0 ? round(($clicks / $views) * 100, 2) : 0,
                'earnings_xaf' => (int) ($clk->earnings_xaf ?? 0),
                'pending_xaf' => (int) ($clk->pending_xaf ?? 0),
                'released_xaf' => (int) ($clk->released_xaf ?? 0),
            ];
        })->sortByDesc('earnings_xaf')->values();

        // Performance en direct par site web
        $impBySite = Impression::query()
            ->where('publisher_id', $user->id)
            ->whereNotNull('site_id')
            ->select(['site_id', DB::raw('COUNT(*) as impressions')])
            ->groupBy('site_id')
            ->pluck('impressions', 'site_id');

        $clkBySite = Click::query()
            ->where('publisher_id', $user->id)
            ->whereNotNull('site_id')
            ->selectRaw('
                site_id,
                COALESCE(SUM(CASE WHEN is_valid = 1 THEN 1 ELSE 0 END), 0) as valid_clicks,
                COALESCE(SUM(CASE WHEN is_valid = 0 THEN 1 ELSE 0 END), 0) as blocked_clicks,
                COALESCE(SUM(CASE WHEN is_valid = 1 THEN publisher_earning_xaf ELSE 0 END), 0) as earnings_xaf
            ')
            ->groupBy('site_id')
            ->get()
            ->keyBy('site_id');

        $bySite = $user->sites()->get()->map(function (Site $site) use ($impBySite, $clkBySite) {
            $views = (int) ($impBySite->get($site->id) ?? 0);
            $clk = $clkBySite->get($site->id);
            $clicks = (int) ($clk->valid_clicks ?? 0);

            return [
                'id' => $site->id,
                'name' => $site->name,
                'domain' => $site->domain,
                'impressions' => $views,
                'valid_clicks' => $clicks,
                'blocked_clicks' => (int) ($clk->blocked_clicks ?? 0),
                'ctr' => $views > 0 ? round(($clicks / $views) * 100, 2) : 0,
                'earnings_xaf' => (int) ($clk->earnings_xaf ?? 0),
            ];
        })->sortByDesc('earnings_xaf')->values();

        // Performance en direct par publication sociale
        $clkBySocial = Click::query()
            ->where('publisher_id', $user->id)
            ->whereNotNull('social_placement_id')
            ->selectRaw('
                social_placement_id,
                COALESCE(SUM(CASE WHEN is_valid = 1 THEN 1 ELSE 0 END), 0) as valid_clicks,
                COALESCE(SUM(CASE WHEN is_valid = 0 THEN 1 ELSE 0 END), 0) as blocked_clicks,
                COALESCE(SUM(CASE WHEN is_valid = 1 THEN publisher_earning_xaf ELSE 0 END), 0) as earnings_xaf
            ')
            ->groupBy('social_placement_id')
            ->get()
            ->keyBy('social_placement_id');

        $bySocial = SocialPlacement::query()
            ->with(['campaign', 'socialAccount'])
            ->where('publisher_id', $user->id)
            ->latest()
            ->get()
            ->map(function (SocialPlacement $p) use ($clkBySocial) {
                $clk = $clkBySocial->get($p->id);

                return [
                    'id' => $p->id,
                    'campaign_name' => $p->campaign?->name ?? 'Campagne',
                    'platform' => $p->socialAccount?->platform?->label() ?? 'Social',
                    'account_name' => $p->socialAccount?->account_name ?? '',
                    'valid_clicks' => (int) ($clk->valid_clicks ?? 0),
                    'blocked_clicks' => (int) ($clk->blocked_clicks ?? 0),
                    'earnings_xaf' => (int) ($clk->earnings_xaf ?? 0),
                ];
            });

        // Répartition par appareil (directement depuis impressions & clics)
        $impByDevice = Impression::query()
            ->where('publisher_id', $user->id)
            ->select(['device_type', DB::raw('COUNT(*) as impressions')])
            ->groupBy('device_type')
            ->pluck('impressions', 'device_type');

        $clkByDevice = Click::query()
            ->where('publisher_id', $user->id)
            ->where('is_valid', true)
            ->selectRaw('device_type, COUNT(*) as clicks, COALESCE(SUM(publisher_earning_xaf), 0) as earnings_xaf')
            ->groupBy('device_type')
            ->get()
            ->keyBy('device_type');

        $devices = $impByDevice->keys()->merge($clkByDevice->keys())->unique()->values();
        $byDevice = $devices->map(fn ($dev) => [
            'device' => $dev ?: 'inconnu',
            'impressions' => (int) ($impByDevice->get($dev) ?? 0),
            'clicks' => (int) ($clkByDevice->get($dev)?->clicks ?? 0),
            'earnings_xaf' => (int) ($clkByDevice->get($dev)?->earnings_xaf ?? 0),
        ])->values();

        // Géolocalisation (top 12 villes)
        $byGeo = Click::query()
            ->where('publisher_id', $user->id)
            ->where('is_valid', true)
            ->selectRaw("COALESCE(country, 'Cameroun') as country, COALESCE(city, 'Inconnue') as city, COUNT(*) as clicks, COALESCE(SUM(publisher_earning_xaf), 0) as earnings_xaf")
            ->groupBy('country', 'city')
            ->orderByDesc('clicks')
            ->limit(12)
            ->get()
            ->map(fn ($r) => [
                'country' => $r->country,
                'city' => $r->city,
                'clicks' => (int) $r->clicks,
                'earnings_xaf' => (int) $r->earnings_xaf,
            ]);

        // Évolution sur 14 jours en direct
        $start14 = now()->subDays(13)->startOfDay();
        $impDaily = Impression::query()
            ->where('publisher_id', $user->id)
            ->where('created_at', '>=', $start14)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as impressions')
            ->groupBy('day')
            ->pluck('impressions', 'day');

        $clkDaily = Click::query()
            ->where('publisher_id', $user->id)
            ->where('created_at', '>=', $start14)
            ->selectRaw('
                DATE(created_at) as day,
                COALESCE(SUM(CASE WHEN is_valid = 1 THEN 1 ELSE 0 END), 0) as valid_clicks,
                COALESCE(SUM(CASE WHEN is_valid = 0 THEN 1 ELSE 0 END), 0) as blocked_clicks,
                COALESCE(SUM(CASE WHEN is_valid = 1 THEN publisher_earning_xaf ELSE 0 END), 0) as earnings_xaf
            ')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $dailyStats = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $clkRow = $clkDaily->get($d);
            $dailyStats[] = [
                'date' => Carbon::parse($d)->format('d/m'),
                'impressions' => (int) ($impDaily->get($d) ?? 0),
                'valid_clicks' => (int) ($clkRow->valid_clicks ?? 0),
                'blocked_clicks' => (int) ($clkRow->blocked_clicks ?? 0),
                'earnings_xaf' => (int) ($clkRow->earnings_xaf ?? 0),
            ];
        }

        // 20 derniers clics en temps réel
        $recentClicks = Click::query()
            ->with(['campaign:id,name', 'site:id,name'])
            ->where('publisher_id', $user->id)
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(fn (Click $c) => [
                'id' => $c->id,
                'created_at' => $c->created_at?->format('d/m/Y H:i:s'),
                'campaign_name' => $c->campaign?->name ?? 'Campagne',
                'source' => $c->site?->name ?? ($c->social_placement_id ? 'Réseau Social' : 'Direct'),
                'city' => $c->city ?: 'Inconnue',
                'device_type' => $c->device_type ?: 'desktop',
                'is_valid' => (bool) $c->is_valid,
                'invalid_reason' => $c->invalid_reason,
                'earning_xaf' => (int) $c->publisher_earning_xaf,
                'earning_status' => $c->earning_status,
            ]);

        // Performance IA des emplacements (Thompson Sampling en temps réel)
        $siteIds = $user->sites()->pluck('id');
        $slotPerformance = AdSlotMetric::query()
            ->with('site:id,name')
            ->whereIn('site_id', $siteIds)
            ->orderByDesc('earnings_xaf')
            ->orderByDesc('clicks_count')
            ->get()
            ->map(function (AdSlotMetric $m) {
                $imp = $m->impressions_count;
                $viewable = $m->viewable_count;
                $clicks = $m->clicks_count;

                return [
                    'id' => $m->id,
                    'site_name' => $m->site?->name ?? "Site #{$m->site_id}",
                    'slot_key' => $m->slot_key,
                    'device_type' => $m->device_type,
                    'impressions_count' => $imp,
                    'viewable_count' => $viewable,
                    'viewability_rate' => $imp > 0 ? round(($viewable / $imp) * 100, 1) : 0,
                    'clicks_count' => $clicks,
                    'ctr' => $imp > 0 ? round(($clicks / $imp) * 100, 2) : 0,
                    'earnings_xaf' => $m->earnings_xaf,
                ];
            });

        return Inertia::render('Publisher/Stats/Index', [
            'summary' => [
                'total_impressions' => $totalImpressions,
                'today_impressions' => $todayImpressions,
                'unique_visitors' => $uniqueVisitors,
                'valid_clicks' => $validClicks,
                'today_clicks' => (int) ($todayClickTotals->valid_clicks ?? 0),
                'blocked_clicks' => $blockedClicks,
                'ctr' => $ctr,
                'total_earnings_xaf' => $totalEarnings,
                'today_earnings_xaf' => (int) ($todayClickTotals->earnings ?? 0),
                'pending_earnings_xaf' => (int) ($clickTotals->pending_earnings ?? 0),
                'released_earnings_xaf' => (int) ($clickTotals->released_earnings ?? 0),
                'available_balance_xaf' => (int) $wallet->available_balance_xaf,
                'pending_balance_xaf' => (int) $wallet->pending_balance_xaf,
                'rpm_xaf' => $rpm,
                'avg_cpc_xaf' => $avgCpc,
            ],
            'byCampaign' => $byCampaign,
            'bySite' => $bySite,
            'bySocial' => $bySocial,
            'byDevice' => $byDevice,
            'byGeo' => $byGeo,
            'dailyStats' => $dailyStats,
            'recentClicks' => $recentClicks,
            'slotPerformance' => $slotPerformance,
        ]);
    }
}
