<?php

namespace App\Enums;

/**
 * Types de diffusion d'une campagne.
 */
enum CampaignType: string
{
    case Web = 'web';
    case Social = 'social';

    public function label(): string
    {
        return match ($this) {
            self::Web => __('Sites Web'),
            self::Social => __('Réseaux Sociaux'),
        };
    }
}
