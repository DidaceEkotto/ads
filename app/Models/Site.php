<?php

namespace App\Models;

use App\Enums\SiteStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'publisher_id',
    'name',
    'domain',
    'category',
    'language',
    'status',
    'verification_token',
    'site_token',
    'auto_ads_enabled',
    'allowed_formats',
    'custom_cdn_host',
    'rejection_reason',
    'reviewed_by',
    'reviewed_at',
])]
class Site extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SiteStatus::class,
            'auto_ads_enabled' => 'boolean',
            'allowed_formats' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publisher_id');
    }

    public function adZones(): HasMany
    {
        return $this->hasMany(AdZone::class);
    }

    public function isApproved(): bool
    {
        return $this->status === SiteStatus::Approved;
    }

    /**
     * Génère la balise unique à copier dans le <head> du site (mode Auto-Ads).
     */
    public function autoAdsHeadSnippet(?string $customHost = null): string
    {
        $baseUrl = rtrim($customHost ?: $this->custom_cdn_host ?: config('app.url'), '/');

        return "<script async src=\"{$baseUrl}/a/{$this->site_token}.js\"></script>";
    }
}
