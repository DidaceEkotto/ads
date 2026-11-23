<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'campaign_id',
    'title',
    'body_text',
    'destination_url',
    'media_type',
    'media_path',
    'format',
    'file_size_bytes',
    'is_active',
])]
class Creative extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function mediaUrl(): string
    {
        return Storage::disk('public')->url($this->media_path);
    }
}
