<?php

namespace App\Enums;

/**
 * Réseaux sociaux supportés pour les posts sponsorisés.
 */
enum SocialPlatform: string
{
    case Facebook = 'facebook';
    case X = 'x';
    case LinkedIn = 'linkedin';

    public function label(): string
    {
        return match ($this) {
            self::Facebook => 'Facebook',
            self::X => 'X (Twitter)',
            self::LinkedIn => 'LinkedIn',
        };
    }
}
