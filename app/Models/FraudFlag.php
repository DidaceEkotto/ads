<?php

namespace App\Models;

use App\Enums\FraudReason;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'click_id',
    'campaign_id',
    'publisher_id',
    'reason',
    'score',
    'ip_address',
    'details',
    'is_resolved',
])]
class FraudFlag extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => FraudReason::class,
            'score' => 'integer',
            'details' => 'array',
            'is_resolved' => 'boolean',
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
}
