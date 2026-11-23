<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'available_balance_xaf', 'pending_balance_xaf', 'total_earned_xaf', 'total_spent_xaf'])]
class Wallet extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'available_balance_xaf' => 'integer',
            'pending_balance_xaf' => 'integer',
            'total_earned_xaf' => 'integer',
            'total_spent_xaf' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
