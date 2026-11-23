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
}
