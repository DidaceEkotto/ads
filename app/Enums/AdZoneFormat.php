<?php

namespace App\Enums;

/**
 * Formats des emplacements publicitaires web.
 */
enum AdZoneFormat: string
{
    case Banner728x90 = 'banner_728x90';
    case Square300x250 = 'square_300x250';
    case Vertical160x600 = 'vertical_160x600';
    case Native = 'native';

    public function label(): string
    {
        return match ($this) {
            self::Banner728x90 => __('Bannière (728x90)'),
            self::Square300x250 => __('Carré (300x250)'),
            self::Vertical160x600 => __('Vertical (160x600)'),
            self::Native => __('Format natif recommandé'),
        };
    }

    /**
     * Dimensions CSS [largeur, hauteur] en pixels (null pour le format natif adaptatif).
     *
     * @return array{width: int|null, height: int|null}
     */
    public function dimensions(): array
    {
        return match ($this) {
            self::Banner728x90 => ['width' => 728, 'height' => 90],
            self::Square300x250 => ['width' => 300, 'height' => 250],
            self::Vertical160x600 => ['width' => 160, 'height' => 600],
            self::Native => ['width' => null, 'height' => null],
        };
    }
}
