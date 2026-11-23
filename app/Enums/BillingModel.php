<?php

namespace App\Enums;

/**
 * Modèles de facturation des campagnes publicitaires.
 */
enum BillingModel: string
{
    case Cpc = 'cpc';
    case Cpm = 'cpm';
    case FlatPost = 'flat_post';

    public function label(): string
    {
        return match ($this) {
            self::Cpc => __('Coût par clic (CPC)'),
            self::Cpm => __('Coût pour 1 000 impressions (CPM)'),
            self::FlatPost => __('Forfait par post social'),
        };
    }
}
