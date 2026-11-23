<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'reference',
    'idempotency_key',
    'wallet_id',
    'type',
    'direction',
    'amount_xaf',
    'balance_after_xaf',
    'status',
    'gateway',
    'gateway_reference',
    'referenceable_type',
    'referenceable_id',
    'description',
    'metadata',
])]
class Transaction extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount_xaf' => 'integer',
            'balance_after_xaf' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function referenceable(): MorphTo
    {
        return $this->morphTo();
    }
}
