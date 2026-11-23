<?php

namespace App\Jobs;

use App\Models\Click;
use App\Models\Impression;
use App\Models\Site;
use App\Models\StatsDaily;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Agrège les impressions et clics d'une journée vers la table stats_daily de manière idempotente.
 */
class AggregateDailyStatsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public ?string $date = null) {}

    public function handle(): void
    {
        $targetDate = $this->date ? Carbon::parse($this->date)->toDateString() : now()->toDateString();
        $start = Carbon::parse($targetDate)->startOfDay();
        $end = Carbon::parse($targetDate)->endOfDay();

        $buckets = [];

        $impRows = Impression::query()
            ->select([
                'campaign_id',
                'publisher_id',
                DB::raw('COALESCE(site_id, 0) as site_id'),
                DB::raw('COALESCE(ad_zone_id, 0) as ad_zone_id'),
                'device_type',
                DB::raw('COUNT(*) as cnt'),
            ])
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('campaign_id', 'publisher_id', 'site_id', 'ad_zone_id', 'device_type')
            ->get();

        foreach ($impRows as $row) {
            $key = "{$row->campaign_id}|{$row->publisher_id}|{$row->site_id}|{$row->ad_zone_id}|{$row->device_type}";
            $buckets[$key] = [
                'date' => $targetDate,
                'campaign_id' => (int) $row->campaign_id,
                'publisher_id' => (int) $row->publisher_id,
                'site_id' => (int) $row->site_id,
                'ad_zone_id' => (int) $row->ad_zone_id,
                'device_type' => (string) $row->device_type,
                'impressions_count' => (int) $row->cnt,
                'valid_clicks_count' => 0,
                'invalid_clicks_count' => 0,
                'adblock_hits_count' => 0,
                'spend_xaf' => 0,
                'publisher_earning_xaf' => 0,
                'platform_fee_xaf' => 0,
            ];
        }

        $clickRows = Click::query()
            ->select([
                'campaign_id',
                'publisher_id',
                DB::raw('COALESCE(site_id, 0) as site_id'),
                DB::raw('COALESCE(ad_zone_id, 0) as ad_zone_id'),
                'device_type',
                DB::raw('SUM(CASE WHEN is_valid = 1 THEN 1 ELSE 0 END) as valid_cnt'),
                DB::raw('SUM(CASE WHEN is_valid = 0 THEN 1 ELSE 0 END) as invalid_cnt'),
                DB::raw('SUM(CASE WHEN is_valid = 1 THEN cost_xaf ELSE 0 END) as spend'),
                DB::raw('SUM(CASE WHEN is_valid = 1 THEN publisher_earning_xaf ELSE 0 END) as pub_earn'),
                DB::raw('SUM(CASE WHEN is_valid = 1 THEN platform_fee_xaf ELSE 0 END) as plat_fee'),
            ])
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('campaign_id', 'publisher_id', 'site_id', 'ad_zone_id', 'device_type')
            ->get();

        foreach ($clickRows as $row) {
            $key = "{$row->campaign_id}|{$row->publisher_id}|{$row->site_id}|{$row->ad_zone_id}|{$row->device_type}";
            if (! isset($buckets[$key])) {
                $buckets[$key] = [
                    'date' => $targetDate,
                    'campaign_id' => (int) $row->campaign_id,
                    'publisher_id' => (int) $row->publisher_id,
                    'site_id' => (int) $row->site_id,
                    'ad_zone_id' => (int) $row->ad_zone_id,
                    'device_type' => (string) $row->device_type,
                    'impressions_count' => 0,
                    'valid_clicks_count' => 0,
                    'invalid_clicks_count' => 0,
                    'adblock_hits_count' => 0,
                    'spend_xaf' => 0,
                    'publisher_earning_xaf' => 0,
                    'platform_fee_xaf' => 0,
                ];
            }

            $buckets[$key]['valid_clicks_count'] = (int) $row->valid_cnt;
            $buckets[$key]['invalid_clicks_count'] = (int) $row->invalid_cnt;
            $buckets[$key]['spend_xaf'] = (int) $row->spend;
            $buckets[$key]['publisher_earning_xaf'] = (int) $row->pub_earn;
            $buckets[$key]['platform_fee_xaf'] = (int) $row->plat_fee;
        }

        // Intégration des compteurs adblock par site depuis Redis/Cache
        $siteIds = Site::query()->pluck('publisher_id', 'id');
        foreach ($siteIds as $siteId => $publisherId) {
            $abHits = (int) Cache::get("rt_adblock:{$targetDate}:s:{$siteId}", 0);
            if ($abHits > 0) {
                $key = "0|{$publisherId}|{$siteId}|0|";
                $buckets[$key] = [
                    'date' => $targetDate,
                    'campaign_id' => 0,
                    'publisher_id' => (int) $publisherId,
                    'site_id' => (int) $siteId,
                    'ad_zone_id' => 0,
                    'device_type' => '',
                    'impressions_count' => 0,
                    'valid_clicks_count' => 0,
                    'invalid_clicks_count' => 0,
                    'adblock_hits_count' => $abHits,
                    'spend_xaf' => 0,
                    'publisher_earning_xaf' => 0,
                    'platform_fee_xaf' => 0,
                ];
            }
        }

        foreach ($buckets as $row) {
            StatsDaily::query()->updateOrCreate(
                [
                    'date' => $row['date'],
                    'campaign_id' => $row['campaign_id'],
                    'publisher_id' => $row['publisher_id'],
                    'site_id' => $row['site_id'],
                    'ad_zone_id' => $row['ad_zone_id'],
                    'device_type' => $row['device_type'],
                ],
                [
                    'impressions_count' => $row['impressions_count'],
                    'valid_clicks_count' => $row['valid_clicks_count'],
                    'invalid_clicks_count' => $row['invalid_clicks_count'],
                    'adblock_hits_count' => $row['adblock_hits_count'],
                    'spend_xaf' => $row['spend_xaf'],
                    'publisher_earning_xaf' => $row['publisher_earning_xaf'],
                    'platform_fee_xaf' => $row['platform_fee_xaf'],
                ]
            );
        }
    }
}
