<?php

namespace App\Enums;

/**
 * Motifs de suspicion ou d'invalidation anti-fraude.
 */
enum FraudReason: string
{
    case BotUserAgent = 'bot_ua';
    case DuplicateClick = 'duplicate_click';
    case SelfClick = 'self_click';
    case RateLimit = 'rate_limit';
    case PreviewBot = 'preview_bot';

    public function label(): string
    {
        return match ($this) {
            self::BotUserAgent => __('Robot détecté (User-Agent)'),
            self::DuplicateClick => __('Clic dupliqué'),
            self::SelfClick => __('Auto-clic éditeur'),
            self::RateLimit => __('Dépassement de fréquence'),
            self::PreviewBot => __('Robot d\'aperçu social'),
        };
    }
}
