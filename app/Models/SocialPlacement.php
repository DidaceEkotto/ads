<?php

namespace App\Models;

use App\Enums\SocialPlacementStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'campaign_id',
    'publisher_id',
    'social_account_id',
    'post_url',
    'has_disclosure_tag',
    'status',
    'published_at',
    'min_duration_ends_at',
    'rejection_reason',
])]
class SocialPlacement extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SocialPlacementStatus::class,
            'has_disclosure_tag' => 'boolean',
            'published_at' => 'datetime',
            'min_duration_ends_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publisher_id');
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function trackingLink(): HasOne
    {
        return $this->hasOne(TrackingLink::class);
    }
}
