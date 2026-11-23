<?php

namespace App\Models;

use App\Enums\AdZoneFormat;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['site_id', 'name', 'token', 'format', 'is_native', 'is_active'])]
class AdZone extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'format' => AdZoneFormat::class,
            'is_native' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Génère le code d'intégration JS léger avec des identifiants et classes CSS neutres
     * (évite les mots-clés détectés par les bloqueurs).
     */
    public function jsSnippet(?string $customHost = null): string
    {
        $baseUrl = rtrim($customHost ?: config('app.url'), '/');
        $slotId = 'cm-rec-'.$this->token;

        return "<div id=\"{$slotId}\" data-cm-slot=\"{$this->token}\"></div>\n"
            ."<script async src=\"{$baseUrl}/d/{$this->token}.js\"></script>";
    }

    /**
     * Génère une variante iframe légère pour les CMS restrictifs.
     */
    public function iframeSnippet(?string $customHost = null): string
    {
        $baseUrl = rtrim($customHost ?: config('app.url'), '/');
        $dims = $this->format->dimensions();
        $w = $dims['width'] ? $dims['width'].'px' : '100%';
        $h = $dims['height'] ? $dims['height'].'px' : '250px';

        return "<iframe src=\"{$baseUrl}/d/frame/{$this->token}\" style=\"width:{$w};height:{$h};border:0;overflow:hidden;\" loading=\"lazy\"></iframe>";
    }
}
