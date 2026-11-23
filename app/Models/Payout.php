<?php

namespace App\Models;

use App\Enums\PayoutStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'reference',
    'publisher_id',
    'payment_method_id',
    'amount_xaf',
    'status',
    'admin_note',
    'gateway_reference',
    'proof_path',
    'processed_by',
    'processed_at',
])]
class Payout extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PayoutStatus::class,
            'amount_xaf' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publisher_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function proofUrl(): ?string
    {
        return $this->proof_path
            ? Storage::disk('public')->url($this->proof_path)
            : null;
    }
}
