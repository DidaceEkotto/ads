<?php

namespace App\Enums;

/**
 * Statuts du cycle de vie d'une campagne.
 */
enum CampaignStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Brouillon'),
            self::PendingReview => __('En attente de validation'),
            self::Active => __('Active'),
            self::Paused => __('En pause'),
            self::Completed => __('Terminée'),
            self::Rejected => __('Rejetée'),
        };
    }
}
