<?php

namespace App\Enums;

/**
 * Statuts d'un placement (post sponsorisé) sur un réseau social.
 */
enum SocialPlacementStatus: string
{
    case PendingProof = 'pending_proof';
    case PendingReview = 'pending_review';
    case Active = 'active';
    case Completed = 'completed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PendingProof => __('En attente de preuve'),
            self::PendingReview => __('En cours de vérification'),
            self::Active => __('Actif'),
            self::Completed => __('Terminé'),
            self::Rejected => __('Rejeté'),
        };
    }
}
