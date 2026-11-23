<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Gère l'upload sécurisé des visuels publicitaires (image < 150 Ko, GIF < 500 Ko, vidéo courte < 1 Mo).
 */
class CreativeUploadService
{
    public const MAX_IMAGE_BYTES = 153600; // 150 Ko

    public const MAX_GIF_BYTES = 512000;   // 500 Ko

    public const MAX_VIDEO_BYTES = 1048576; // 1 Mo

    private const ALLOWED_MIMES = [
        'image/jpeg' => ['type' => 'image', 'ext' => 'jpg'],
        'image/png' => ['type' => 'image', 'ext' => 'png'],
        'image/webp' => ['type' => 'image', 'ext' => 'webp'],
        'image/gif' => ['type' => 'gif', 'ext' => 'gif'],
        'video/mp4' => ['type' => 'video', 'ext' => 'mp4'],
        'video/webm' => ['type' => 'video', 'ext' => 'webm'],
    ];

    /**
     * Valide et enregistre un visuel ou une vidéo courte en le renommant avec un UUID aléatoire.
     *
     * @return array{media_type: string, media_path: string, file_size_bytes: int}
     */
    public function store(UploadedFile $file): array
    {
        $realMime = $file->getMimeType();
        if (! $realMime || ! isset(self::ALLOWED_MIMES[$realMime])) {
            throw new InvalidArgumentException(__('Format de fichier non autorisé. Utilisez JPG, PNG, WebP, GIF, MP4 ou WebM.'));
        }

        $meta = self::ALLOWED_MIMES[$realMime];

        // Vérifie que le contenu binaire correspond bien à une image valide pour les images/GIF
        if ($meta['type'] !== 'video' && @getimagesize($file->getRealPath()) === false) {
            throw new InvalidArgumentException(__('Le fichier téléversé n\'est pas une image valide.'));
        }

        $size = (int) $file->getSize();
        $maxBytes = match ($meta['type']) {
            'gif' => self::MAX_GIF_BYTES,
            'video' => self::MAX_VIDEO_BYTES,
            default => self::MAX_IMAGE_BYTES,
        };

        if ($size <= 0 || $size > $maxBytes) {
            $limitKb = (int) ($maxBytes / 1024);
            throw new InvalidArgumentException(
                __('Le fichier dépasse la limite locale autorisée (:limit Ko max).', ['limit' => $limitKb])
            );
        }

        $directory = 'creatives/'.now()->format('Y/m');
        $filename = Str::uuid().'.'.$meta['ext'];
        $path = Storage::disk('public')->putFileAs($directory, $file, $filename);

        if ($path === false) {
            throw new InvalidArgumentException(__('Impossible d\'enregistrer le visuel.'));
        }

        return [
            'media_type' => $meta['type'],
            'media_path' => $path,
            'file_size_bytes' => $size,
        ];
    }
}
