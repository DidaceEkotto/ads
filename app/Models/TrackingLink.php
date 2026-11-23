<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['code', 'campaign_id', 'publisher_id', 'social_placement_id', 'is_active'])]
class TrackingLink extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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

    public function socialPlacement(): BelongsTo
    {
        return $this->belongsTo(SocialPlacement::class);
    }

    public function publicUrl(): string
    {
        return route('adserving.social', ['code' => $this->code]);
    }
}
