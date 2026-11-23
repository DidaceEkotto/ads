<?php

namespace App\Models;

use App\Enums\BillingModel;
use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'advertiser_id',
    'name',
    'type',
    'billing_model',
    'status',
    'total_budget_xaf',
    'daily_budget_xaf',
    'spent_xaf',
    'unit_price_xaf',
    'starts_at',
    'ends_at',
    'rejection_reason',
    'reviewed_by',
    'reviewed_at',
])]
class Campaign extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CampaignType::class,
            'billing_model' => BillingModel::class,
            'status' => CampaignStatus::class,
            'total_budget_xaf' => 'integer',
            'daily_budget_xaf' => 'integer',
            'spent_xaf' => 'integer',
            'unit_price_xaf' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advertiser_id');
    }

    public function creatives(): HasMany
    {
        return $this->hasMany(Creative::class);
    }

    public function targeting(): HasOne
    {
        return $this->hasOne(CampaignTargeting::class);
    }

    public function remainingBudgetXaf(): int
    {
        return max(0, $this->total_budget_xaf - $this->spent_xaf);
    }
}
