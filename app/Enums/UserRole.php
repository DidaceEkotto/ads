<?php

namespace App\Enums;

/**
 * Rôles des utilisateurs sur la plateforme.
 */
enum UserRole: string
{
    case Advertiser = 'advertiser';
    case Publisher = 'publisher';
    case Admin = 'admin';

    /**
     * Retourne le libellé lisible du rôle.
     */
    public function label(): string
    {
        return match ($this) {
            self::Advertiser => __('Annonceur'),
            self::Publisher => __('Éditeur'),
            self::Admin => __('Administrateur'),
        };
    }

    /**
     * Retourne la route d'accueil par défaut selon le rôle.
     */
    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Advertiser => 'advertiser.dashboard',
            self::Publisher => 'publisher.dashboard',
            self::Admin => 'admin.dashboard',
        };
    }
}
