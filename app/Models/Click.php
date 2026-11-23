<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'impression_id',
    'tracking_link_id',
    'campaign_id',
    'creative_id',
    'site_id',
    'ad_zone_id',
    'social_placement_id',
    'publisher_id',
    'visitor_hash',
    'ip_address',
    'country',
    'user_agent',
    'referer',
    'city',
    'device_type',
    'is_valid',
    'fraud_score',
    'invalid_reason',
    'cost_xaf',
    'publisher_earning_xaf',
    'platform_fee_xaf',
    'earning_status',
    'available_at',
    'created_at',
])]
class Click extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_valid' => 'boolean',
            'fraud_score' => 'integer',
            'cost_xaf' => 'integer',
            'publisher_earning_xaf' => 'integer',
            'platform_fee_xaf' => 'integer',
            'available_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
