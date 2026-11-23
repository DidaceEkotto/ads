<?php

namespace App\Services\AntiFraud;

use App\Enums\FraudReason;
use App\Models\Click;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Évalue la légitimité d'un clic et attribue un score de suspicion (0-100).
 */
class ClickFraudDetector
{
    /**
     * @return array{is_valid: bool, fraud_score: int, reason: FraudReason|null}
     */
    public function evaluate(
        int $campaignId,
        int $publisherId,
        string $visitorHash,
        string $ipAddress,
        ?string $userAgent,
        ?int $authenticatedUserId = null
    ): array {
        // 1. Auto-clic éditeur
        if ($authenticatedUserId !== null && $authenticatedUserId === $publisherId) {
            return [
                'is_valid' => false,
                'fraud_score' => 100,
                'reason' => FraudReason::SelfClick,
            ];
        }

        // 2. Robot d'aperçu de lien social (Facebookexternalhit, Twitterbot, LinkedInBot...)
        if (BotUserAgentList::isPreviewBot($userAgent)) {
            return [
                'is_valid' => false,
                'fraud_score' => 95,
                'reason' => FraudReason::PreviewBot,
            ];
        }

        // 3. Robot générique / User-Agent vide / script automatisé
        if (BotUserAgentList::isGenericBot($userAgent)) {
            return [
                'is_valid' => false,
                'fraud_score' => 90,
                'reason' => FraudReason::BotUserAgent,
            ];
        }

        // 4. Limitation de débit par visiteur / IP (max 10 clics par heure)
        $rateKey = "click_rl:{$ipAddress}";
        if (RateLimiter::tooManyAttempts($rateKey, 10)) {
            return [
                'is_valid' => false,
                'fraud_score' => 85,
                'reason' => FraudReason::RateLimit,
            ];
        }
        RateLimiter::hit($rateKey, 3600);

        // 5. Clic dupliqué sur la même campagne par le même visiteur sur 24 h
        $alreadyClicked = Click::query()
            ->where('campaign_id', $campaignId)
            ->where('visitor_hash', $visitorHash)
            ->where('is_valid', true)
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();

        if ($alreadyClicked) {
            return [
                'is_valid' => false,
                'fraud_score' => 75,
                'reason' => FraudReason::DuplicateClick,
            ];
        }

        return [
            'is_valid' => true,
            'fraud_score' => 0,
            'reason' => null,
        ];
    }
}
