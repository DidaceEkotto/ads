<?php

namespace App\Enums;

/**
 * Statuts d'une demande de retrait éditeur.
 */
enum PayoutStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Paid = 'paid';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('En attente'),
            self::Processing => __('En cours de traitement'),
            self::Paid => __('Payé'),
            self::Rejected => __('Rejeté'),
        };
    }
}
