<?php

namespace App\Services\AdSelection;

use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\TransactionType;
use App\Models\AdZone;
use App\Models\Campaign;
use App\Models\Creative;
use App\Models\Site;
use App\Models\Transaction;
use Illuminate\Support\Collection;

/**
 * Sélectionne une publicité adaptée à un emplacement ou un site (budget, ciblage, format, rotation).
 */
class AdSelectorService
{
    /**
     * Retourne une créative éligible pour l'emplacement donné, ou null si aucune ne correspond.
     *
     * @param  array{city?: string|null, device?: string, language?: string}  $context
     */
    public function selectForZone(AdZone $zone, array $context = []): ?Creative
    {
        $site = $zone->site;
        if (! $zone->is_active || ! $site || ! $site->isApproved()) {
            return null;
        }

        return $this->selectForSiteAndFormat($site, $zone->format->value, $context);
    }

    /**
     * Sélectionne une créative éligible pour un site et un format donnés (utilisé par Auto-Ads).
     *
     * @param  array{city?: string|null, device?: string, language?: string}  $context
     */
    public function selectForSiteAndFormat(Site $site, string $format, array $context = []): ?Creative
    {
        if (! $site->isApproved()) {
            return null;
        }

        $now = now();
        $device = $context['device'] ?? 'mobile';
        $city = $context['city'] ?? null;
        $language = $context['language'] ?? $site->language ?? 'fr';
        $category = $site->category;

        /** @var Collection<int, Campaign> $campaigns */
        $campaigns = Campaign::query()
            ->with(['creatives' => function ($q) use ($format) {
                $q->where('is_active', true)
                    ->where('format', $format);
            }, 'targeting', 'advertiser.wallet'])
            ->where('type', CampaignType::Web)
            ->where('status', CampaignStatus::Active)
            ->whereColumn('spent_xaf', '<', 'total_budget_xaf')
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
            ->get();

        $eligibleCreatives = $campaigns->filter(function (Campaign $campaign) use ($device, $city, $language, $category) {
            if ($campaign->creatives->isEmpty()) {
                return false;
            }

            $isPrepaid = Transaction::query()
                ->where('reference_type', Campaign::class)
                ->where('reference_id', $campaign->id)
                ->where('type', TransactionType::AdSpend)
                ->exists();

            if ($isPrepaid) {
                if ($campaign->remainingBudgetXaf() < $campaign->unit_price_xaf) {
                    return false;
                }
            } else {
                $wallet = $campaign->advertiser?->wallet;
                if (! $wallet || $wallet->available_balance_xaf < $campaign->unit_price_xaf) {
                    return false;
                }
            }

            $targeting = $campaign->targeting;
            if (! $targeting) {
                return true;
            }

            if (! empty($targeting->devices) && ! in_array($device, $targeting->devices, true)) {
                return false;
            }

            if (! empty($targeting->languages) && ! in_array($language, $targeting->languages, true)) {
                return false;
            }

            if (! empty($targeting->categories) && ! in_array($category, $targeting->categories, true)) {
                return false;
            }

            if ($city && ! empty($targeting->cities) && ! in_array($city, $targeting->cities, true)) {
                return false;
            }

            return true;
        })->flatMap(fn (Campaign $c) => $c->creatives);

        if ($eligibleCreatives->isEmpty()) {
            return null;
        }

        $excludeIds = array_map('intval', $context['exclude_creative_ids'] ?? []);
        if ($excludeIds !== []) {
            $unseenCreatives = $eligibleCreatives->reject(
                fn (Creative $c) => in_array($c->id, $excludeIds, true)
            );
            if ($unseenCreatives->isNotEmpty()) {
                return $unseenCreatives->random();
            }
        }

        // Rotation aléatoire équitable parmi les créatives éligibles
        return $eligibleCreatives->random();
    }
}
