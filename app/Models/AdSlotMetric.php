<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'site_id',
    'slot_key',
    'device_type',
    'impressions_count',
    'viewable_count',
    'clicks_count',
    'earnings_xaf',
])]
class AdSlotMetric extends Model
{
    protected function casts(): array
    {
        return [
            'impressions_count' => 'integer',
            'viewable_count' => 'integer',
            'clicks_count' => 'integer',
            'earnings_xaf' => 'integer',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public static function recordImpression(int $siteId, string $slotKey, string $deviceType): void
    {
        $row = self::firstOrCreateSlot($siteId, $slotKey, $deviceType);
        $row->increment('impressions_count');
    }

    public static function recordViewable(int $siteId, string $slotKey, string $deviceType): void
    {
        $row = self::firstOrCreateSlot($siteId, $slotKey, $deviceType);
        $row->increment('viewable_count');
    }

    public static function recordClick(int $siteId, string $slotKey, string $deviceType, int $earningXaf): void
    {
        $row = self::firstOrCreateSlot($siteId, $slotKey, $deviceType);
        $row->increment('clicks_count');
        if ($earningXaf > 0) {
            $row->increment('earnings_xaf', $earningXaf);
        }
    }

    private static function firstOrCreateSlot(int $siteId, string $slotKey, string $deviceType): self
    {
        return self::query()->firstOrCreate(
            [
                'site_id' => $siteId,
                'slot_key' => $slotKey,
                'device_type' => $deviceType ?: 'desktop',
            ],
            [
                'impressions_count' => 0,
                'viewable_count' => 0,
                'clicks_count' => 0,
                'earnings_xaf' => 0,
            ]
        );
    }
}
