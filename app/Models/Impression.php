<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'campaign_id',
    'creative_id',
    'site_id',
    'ad_zone_id',
    'publisher_id',
    'visitor_hash',
    'ip_address',
    'country',
    'city',
    'device_type',
    'is_adblock_detected',
    'cost_xaf',
    'created_at',
])]
class Impression extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_adblock_detected' => 'boolean',
            'cost_xaf' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
