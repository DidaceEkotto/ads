<?php

namespace App\Enums;

/**
 * Statuts de validation d'un site web éditeur.
 */
enum SiteStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('En attente'),
            self::Approved => __('Approuvé'),
            self::Rejected => __('Rejeté'),
            self::Suspended => __('Suspendu'),
        };
    }
}
