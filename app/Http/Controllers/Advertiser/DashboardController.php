<?php

namespace App\Http\Controllers\Advertiser;

use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
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
        $campaigns = $user->campaigns()->latest()->get();
        $campaignIds = $campaigns->pluck('id');

        $totals = StatsDaily::query()
            ->whereIn('campaign_id', $campaignIds)
            ->selectRaw('COALESCE(SUM(impressions_count), 0) as impressions, COALESCE(SUM(valid_clicks_count), 0) as clicks, COALESCE(SUM(spend_xaf), 0) as spend')
            ->first();

        $impressions = (int) ($totals?->impressions ?? 0);
        $clicks = (int) ($totals?->clicks ?? 0);
        $spend = (int) ($totals?->spend ?? 0);

        // Comparaison 7 derniers jours vs 7 jours précédents (hausse / baisse)
        $currentStart = now()->subDays(6)->toDateString();
        $prevStart = now()->subDays(13)->toDateString();
        $prevEnd = now()->subDays(7)->toDateString();

        $currentPeriod = $this->periodTotals($campaignIds, $currentStart, now()->toDateString());
        $prevPeriod = $this->periodTotals($campaignIds, $prevStart, $prevEnd);

        $currentCtr = $currentPeriod['impressions'] > 0 ? ($currentPeriod['clicks'] / $currentPeriod['impressions']) * 100 : 0;
        $prevCtr = $prevPeriod['impressions'] > 0 ? ($prevPeriod['clicks'] / $prevPeriod['impressions']) * 100 : 0;

        // Courbe journalière sur 14 jours (remplissage des jours à 0 pour une courbe continue)
        $rawDaily = StatsDaily::query()
            ->whereIn('campaign_id', $campaignIds)
            ->where('date', '>=', $prevStart)
            ->select([
                'date',
                DB::raw('SUM(impressions_count) as impressions'),
                DB::raw('SUM(valid_clicks_count) as clicks'),
                DB::raw('SUM(spend_xaf) as spend'),
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
                'spend' => (int) ($row->spend ?? 0),
            ];
        }

        // Comparaison par campagne + tendance 7j vs 7j précédents
        $campTotals = StatsDaily::query()
            ->whereIn('campaign_id', $campaignIds)
            ->select([
                'campaign_id',
                DB::raw('SUM(impressions_count) as impressions'),
                DB::raw('SUM(valid_clicks_count) as clicks'),
                DB::raw('SUM(spend_xaf) as spend'),
            ])
            ->groupBy('campaign_id')
            ->get()
            ->keyBy('campaign_id');

        $campCurrent = StatsDaily::query()
            ->whereIn('campaign_id', $campaignIds)
            ->where('date', '>=', $currentStart)
            ->select(['campaign_id', DB::raw('SUM(valid_clicks_count) as clicks')])
            ->groupBy('campaign_id')
            ->pluck('clicks', 'campaign_id');

        $campPrev = StatsDaily::query()
            ->whereIn('campaign_id', $campaignIds)
            ->whereBetween('date', [$prevStart, $prevEnd])
            ->select(['campaign_id', DB::raw('SUM(valid_clicks_count) as clicks')])
            ->groupBy('campaign_id')
            ->pluck('clicks', 'campaign_id');

        $campaignComparison = $campaigns->map(function (Campaign $c) use ($campTotals, $campCurrent, $campPrev) {
            $t = $campTotals->get($c->id);
            $imp = (int) ($t->impressions ?? 0);
            $clk = (int) ($t->clicks ?? 0);
            $sp = (int) ($t->spend ?? $c->spent_xaf);
            $curC = (int) ($campCurrent->get($c->id) ?? 0);
            $prvC = (int) ($campPrev->get($c->id) ?? 0);

            return [
                'id' => $c->id,
                'name' => $c->name,
                'status' => $c->status->value,
                'status_label' => $c->status->label(),
                'billing_model' => strtoupper($c->billing_model->value),
                'impressions' => $imp,
                'clicks' => $clk,
                'ctr' => $imp > 0 ? round(($clk / $imp) * 100, 2) : 0,
                'cpc' => $clk > 0 ? (int) round($sp / $clk) : $c->unit_price_xaf,
                'spend_xaf' => $sp,
                'total_budget_xaf' => $c->total_budget_xaf,
                'trend_percent' => $this->calcTrend($curC, $prvC),
            ];
        })->values();

        $byDevice = StatsDaily::query()
            ->whereIn('campaign_id', $campaignIds)
            ->where('device_type', '!=', '')
            ->select([
                'device_type',
                DB::raw('SUM(impressions_count) as impressions'),
                DB::raw('SUM(valid_clicks_count) as clicks'),
            ])
            ->groupBy('device_type')
            ->get();

        $lowBudgetCampaigns = $campaigns
            ->where('status', CampaignStatus::Active)
            ->filter(fn (Campaign $c) => $c->total_budget_xaf > 0 && $c->remainingBudgetXaf() <= (int) ($c->total_budget_xaf * 0.2))
            ->values()
            ->map(fn (Campaign $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'remaining_xaf' => $c->remainingBudgetXaf(),
            ]);

        return Inertia::render('Advertiser/Dashboard', [
            'kpis' => [
                'impressions' => $impressions,
                'clicks' => $clicks,
                'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
                'avg_cpc' => $clicks > 0 ? (int) round($spend / $clicks) : 0,
                'spend_xaf' => $spend,
                'trends' => [
                    'impressions' => $this->calcTrend($currentPeriod['impressions'], $prevPeriod['impressions']),
                    'clicks' => $this->calcTrend($currentPeriod['clicks'], $prevPeriod['clicks']),
                    'ctr' => $this->calcTrend($currentCtr, $prevCtr),
                    'spend' => $this->calcTrend($currentPeriod['spend'], $prevPeriod['spend']),
                ],
            ],
            'dailyStats' => $dailyStats,
            'campaignComparison' => $campaignComparison,
            'byDevice' => $byDevice,
            'lowBudgetCampaigns' => $lowBudgetCampaigns,
        ]);
    }

    /**
     * @return array{impressions: int, clicks: int, spend: int}
     */
    private function periodTotals($campaignIds, string $start, string $end): array
    {
        $row = StatsDaily::query()
            ->whereIn('campaign_id', $campaignIds)
            ->whereBetween('date', [$start, $end])
            ->selectRaw('COALESCE(SUM(impressions_count), 0) as impressions, COALESCE(SUM(valid_clicks_count), 0) as clicks, COALESCE(SUM(spend_xaf), 0) as spend')
            ->first();

        return [
            'impressions' => (int) ($row?->impressions ?? 0),
            'clicks' => (int) ($row?->clicks ?? 0),
            'spend' => (int) ($row?->spend ?? 0),
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
