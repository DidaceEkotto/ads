<?php

namespace App\Http\Controllers\Publisher;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Site;
use App\Models\StatsDaily;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DashboardController extends Controller
{
    public function __invoke(Request $request): InertiaResponse
    {
        $user = $request->user();
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $totals = StatsDaily::query()
            ->where('publisher_id', $user->id)
            ->selectRaw('COALESCE(SUM(impressions_count), 0) as impressions, COALESCE(SUM(valid_clicks_count), 0) as clicks, COALESCE(SUM(publisher_earning_xaf), 0) as earnings')
            ->first();

        $todayEarning = (int) StatsDaily::query()
            ->where('publisher_id', $user->id)
            ->whereDate('date', $today)
            ->sum('publisher_earning_xaf');

        $monthEarning = (int) StatsDaily::query()
            ->where('publisher_id', $user->id)
            ->where('date', '>=', $monthStart)
            ->sum('publisher_earning_xaf');

        // Tendances 7j vs 7j précédents
        $currentStart = now()->subDays(6)->toDateString();
        $prevStart = now()->subDays(13)->toDateString();
        $prevEnd = now()->subDays(7)->toDateString();

        $cur = $this->periodTotals($user->id, $currentStart, $today);
        $prv = $this->periodTotals($user->id, $prevStart, $prevEnd);

        // Courbe journalière sur 14 jours
        $rawDaily = StatsDaily::query()
            ->where('publisher_id', $user->id)
            ->where('date', '>=', $prevStart)
            ->select([
                'date',
                DB::raw('SUM(impressions_count) as impressions'),
                DB::raw('SUM(valid_clicks_count) as clicks'),
                DB::raw('SUM(publisher_earning_xaf) as earnings'),
            ])
            ->groupBy('date')
            ->get()
            ->keyBy(fn ($r) => Carbon::parse($r->date)->toDateString());

        $dailyStats = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $row = $rawDaily->get($d);
            $dailyStats[] = [
                'date' => Carbon::parse($d)->format('d/m'),
                'impressions' => (int) ($row->impressions ?? 0),
                'clicks' => (int) ($row->clicks ?? 0),
                'earnings' => (int) ($row->earnings ?? 0),
            ];
        }

        // Performance & taux de blocage estimé par site
        $siteStats = StatsDaily::query()
            ->where('publisher_id', $user->id)
            ->where('site_id', '>', 0)
            ->select([
                'site_id',
                DB::raw('SUM(impressions_count) as impressions'),
                DB::raw('SUM(valid_clicks_count) as clicks'),
                DB::raw('SUM(adblock_hits_count) as adblock_hits'),
                DB::raw('SUM(publisher_earning_xaf) as earnings'),
            ])
            ->groupBy('site_id')
            ->get()
            ->keyBy('site_id');

        $sitesPerformance = $user->sites()->get()->map(function (Site $site) use ($siteStats) {
            $row = $siteStats->get($site->id);
            $imp = (int) ($row->impressions ?? 0);
            $clk = (int) ($row->clicks ?? 0);
            $ab = (int) ($row->adblock_hits ?? 0);
            $totalAttempts = $imp + $ab;

            return [
                'id' => $site->id,
                'name' => $site->name,
                'domain' => $site->domain,
                'status' => $site->status->value,
                'status_label' => $site->status->label(),
                'impressions' => $imp,
                'clicks' => $clk,
                'ctr' => $imp > 0 ? round(($clk / $imp) * 100, 2) : 0,
                'earnings_xaf' => (int) ($row->earnings ?? 0),
                'adblock_rate' => $totalAttempts > 0 ? round(($ab / $totalAttempts) * 100, 1) : 0,
            ];
        });

        return Inertia::render('Publisher/Dashboard', [
            'kpis' => [
                'impressions' => (int) ($totals?->impressions ?? 0),
                'clicks' => (int) ($totals?->clicks ?? 0),
                'today_earning_xaf' => $todayEarning,
                'month_earning_xaf' => $monthEarning,
                'total_earning_xaf' => (int) ($totals?->earnings ?? 0),
                'trends' => [
                    'impressions' => $this->calcTrend($cur['impressions'], $prv['impressions']),
                    'clicks' => $this->calcTrend($cur['clicks'], $prv['clicks']),
                    'earnings' => $this->calcTrend($cur['earnings'], $prv['earnings']),
                ],
            ],
            'dailyStats' => $dailyStats,
            'sitesPerformance' => $sitesPerformance,
            'publisherSharePercent' => (int) Setting::getValue('publisher_share_percent', 70),
        ]);
    }

    /**
     * @return array{impressions: int, clicks: int, earnings: int}
     */
    private function periodTotals(int $publisherId, string $start, string $end): array
    {
        $row = StatsDaily::query()
            ->where('publisher_id', $publisherId)
            ->whereBetween('date', [$start, $end])
            ->selectRaw('COALESCE(SUM(impressions_count), 0) as impressions, COALESCE(SUM(valid_clicks_count), 0) as clicks, COALESCE(SUM(publisher_earning_xaf), 0) as earnings')
            ->first();

        return [
            'impressions' => (int) ($row?->impressions ?? 0),
            'clicks' => (int) ($row?->clicks ?? 0),
            'earnings' => (int) ($row?->earnings ?? 0),
        ];
    }

    private function calcTrend(float|int $current, float|int $previous): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
