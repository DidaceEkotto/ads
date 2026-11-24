<?php

namespace App\Services\AdSelection;

use App\Models\AdSlotMetric;

/**
 * Algorithme Multi-Armed Bandit (Thompson Sampling) en temps réel :
 * classe les emplacements DOM d'un site selon leur taux de visibilité réelle et de clics.
 */
class SlotBanditService
{
    /**
     * Emplacements candidats disponibles sur tout site éditeur (hors navbar).
     *
     * @var array<int, string>
     */
    public const CANDIDATE_SLOTS = [
        'below_navbar',
        'after_p2',
        'sidebar',
        'mid_section',
        'before_footer',
    ];

    /**
     * Retourne les meilleurs emplacements ordonnés par score Thompson Sampling.
     *
     * @return array<int, string>
     */
    public function rankSlots(int $siteId, string $deviceType, int $limit = 4): array
    {
        $metrics = AdSlotMetric::query()
            ->where('site_id', $siteId)
            ->where('device_type', $deviceType ?: 'desktop')
            ->get()
            ->keyBy('slot_key');

        $scores = [];
        foreach (self::CANDIDATE_SLOTS as $slotKey) {
            $m = $metrics->get($slotKey);
            $impressions = (int) ($m?->impressions_count ?? 0);
            $viewables = (int) ($m?->viewable_count ?? 0);
            $clicks = (int) ($m?->clicks_count ?? 0);

            // Paramètres Beta(alpha, beta) : récompense les clics et la visibilité réelle
            $alpha = 1.0 + ($clicks * 10.0) + ($viewables * 0.25);
            $beta = 1.0 + max(0, $impressions - $clicks);

            $sampled = $this->sampleBeta($alpha, $beta);

            // Léger bonus d'exploration pour les nouveaux emplacements peu testés
            if ($impressions < 5) {
                $sampled += 0.15;
            }

            // Légère préférence structurelle pour le haut de page sous la navbar
            if ($slotKey === 'below_navbar') {
                $sampled += 0.05;
            }

            $scores[$slotKey] = $sampled;
        }

        arsort($scores);

        return array_slice(array_keys($scores), 0, max(1, $limit));
    }

    /**
     * Échantillonne une variable aléatoire selon une loi Beta(alpha, beta) via deux lois Gamma.
     */
    private function sampleBeta(float $alpha, float $beta): float
    {
        $x = $this->sampleGamma($alpha);
        $y = $this->sampleGamma($beta);

        return ($x + $y) > 0.0 ? ($x / ($x + $y)) : 0.5;
    }

    /**
     * Échantillonnage Gamma (méthode Marsaglia & Tsang).
     */
    private function sampleGamma(float $shape): float
    {
        if ($shape < 1.0) {
            return $this->sampleGamma($shape + 1.0) * pow($this->uniform01(), 1.0 / $shape);
        }

        $d = $shape - (1.0 / 3.0);
        $c = 1.0 / sqrt(9.0 * $d);

        for ($i = 0; $i < 100; $i++) {
            $x = $this->standardNormal();
            $v = 1.0 + $c * $x;
            if ($v <= 0.0) {
                continue;
            }
            $v3 = $v * $v * $v;
            $u = $this->uniform01();

            if ($u < 1.0 - 0.0331 * ($x * $x) * ($x * $x)) {
                return $d * $v3;
            }
            if (log($u) < 0.5 * $x * $x + $d * (1.0 - $v3 + log($v3))) {
                return $d * $v3;
            }
        }

        return $shape;
    }

    private function standardNormal(): float
    {
        $u1 = max(1e-7, $this->uniform01());
        $u2 = $this->uniform01();

        return sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);
    }

    private function uniform01(): float
    {
        return mt_rand(1, mt_getrandmax() - 1) / mt_getrandmax();
    }
}
