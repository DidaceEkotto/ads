<?php

namespace App\Models;

use App\Enums\SocialPlatform;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'publisher_id',
    'platform',
    'handle',
    'profile_url',
    'followers_count',
    'verification_code',
    'status',
    'rejection_reason',
])]
class SocialAccount extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'platform' => SocialPlatform::class,
            'followers_count' => 'integer',
        ];
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publisher_id');
    }

    public function placements(): HasMany
    {
        return $this->hasMany(SocialPlacement::class);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
