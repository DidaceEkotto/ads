<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'date',
    'campaign_id',
    'publisher_id',
    'site_id',
    'ad_zone_id',
    'device_type',
    'impressions_count',
    'valid_clicks_count',
    'invalid_clicks_count',
    'adblock_hits_count',
    'spend_xaf',
    'publisher_earning_xaf',
    'platform_fee_xaf',
])]
class StatsDaily extends Model
{
    protected $table = 'stats_daily';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'impressions_count' => 'integer',
            'valid_clicks_count' => 'integer',
            'invalid_clicks_count' => 'integer',
            'adblock_hits_count' => 'integer',
            'spend_xaf' => 'integer',
            'publisher_earning_xaf' => 'integer',
            'platform_fee_xaf' => 'integer',
        ];
    }

    /**
     * Incrémente les statistiques du jour en temps réel (sans attendre le cron horaire).
     *
     * @param  array<string, int>  $increments
     */
    public static function recordRealTime(
        int $campaignId,
        int $publisherId,
        ?int $siteId,
        ?int $adZoneId,
        string $deviceType,
        array $increments
    ): void {
        $row = self::query()->firstOrCreate(
            [
                'date' => now()->toDateString(),
                'campaign_id' => $campaignId,
                'publisher_id' => $publisherId,
                'site_id' => $siteId ?? 0,
                'ad_zone_id' => $adZoneId ?? 0,
                'device_type' => $deviceType,
            ],
            [
                'impressions_count' => 0,
                'valid_clicks_count' => 0,
                'invalid_clicks_count' => 0,
                'adblock_hits_count' => 0,
                'spend_xaf' => 0,
                'publisher_earning_xaf' => 0,
                'platform_fee_xaf' => 0,
            ]
        );

        foreach ($increments as $column => $delta) {
            if ($delta > 0) {
                $row->increment($column, $delta);
            }
        }
    }
}
