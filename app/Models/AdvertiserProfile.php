<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'user_id',
    'company_name',
    'logo_path',
    'business_sector',
    'city',
    'postal_box',
    'whatsapp_number',
    'website_url',
    'facebook_url',
    'linkedin_url',
    'x_url',
    'description',
])]
class AdvertiserProfile extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path
            ? Storage::disk('public')->url($this->logo_path)
            : null;
    }
}
