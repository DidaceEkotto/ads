<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'provider', 'account_number', 'account_name', 'is_default', 'is_verified'])]
class PaymentMethod extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_verified' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function providerLabel(): string
    {
        return match ($this->provider) {
            'mtn_momo' => 'MTN Mobile Money',
            'orange_money' => 'Orange Money',
            default => $this->provider,
        };
    }
}
