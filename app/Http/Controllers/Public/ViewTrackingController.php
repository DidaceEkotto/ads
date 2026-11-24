<?php

namespace App\Http\Controllers\Public;

use App\Enums\BillingModel;
use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Models\AdSlotMetric;
use App\Models\Campaign;
use App\Models\Impression;
use App\Models\Setting;
use App\Models\StatsDaily;
use App\Models\User;
use App\Services\AntiFraud\BotUserAgentList;
use App\Services\Billing\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Throwable;

class ViewTrackingController extends Controller
{
    /**
     * Enregistre et facture une vue visible au scroll (IntersectionObserver >= 50%).
     */
    public function record(Request $request, LedgerService $ledger): Response
    {
        $token = (string) $request->input('view_token', '');
        if ($token === '' || BotUserAgentList::isGenericBot($request->userAgent())) {
            return response()->noContent();
        }

        try {
            $payload = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return response()->noContent();
        }

        $campaignId = (int) ($payload['campaign_id'] ?? 0);
        $publisherId = (int) ($payload['publisher_id'] ?? 0);
        $visitorHash = hash('sha256', ($request->ip() ?? '').'|'.($request->userAgent() ?? ''));

        // Remontée temps réel de la visibilité réelle (viewability) pour Thompson Sampling
        if (! empty($payload['site_id']) && ! empty($payload['slot_key'])) {
            $slotDedupKey = "slot_view_dedup:{$payload['site_id']}:{$payload['slot_key']}:{$visitorHash}";
            if (Cache::add($slotDedupKey, 1, now()->addMinutes(10))) {
                AdSlotMetric::recordViewable(
                    (int) $payload['site_id'],
                    (string) $payload['slot_key'],
                    (string) ($payload['device_type'] ?? 'desktop')
                );
            }
        }

        // Déduplication permanente (vue unique par visiteur + publicité + site/page)
        $siteId = (int) ($payload['site_id'] ?? 0);
        $dedupKey = "view_unique:{$campaignId}:{$siteId}:{$visitorHash}";
        if (! Cache::add($dedupKey, 1, now()->addDays(30))) {
            return response()->noContent();
        }

        $alreadyPaidInDb = Impression::query()
            ->where('campaign_id', $campaignId)
            ->where('visitor_hash', $visitorHash)
            ->when($siteId > 0, fn ($q) => $q->where('site_id', $siteId))
            ->where('cost_xaf', '>', 0)
            ->exists();

        if ($alreadyPaidInDb) {
            return response()->noContent();
        }

        $campaign = Campaign::query()->with('advertiser.wallet')->find($campaignId);
        $publisher = User::query()->with(['wallet', 'publisherProfile'])->find($publisherId);

        if (! $campaign || ! $publisher || ! in_array($campaign->billing_model, [BillingModel::Cpc, BillingModel::Cpm], true)) {
            return response()->noContent();
        }

        $viewCostXaf = (int) Setting::getValue('view_unit_price_xaf', 3);
        if ($viewCostXaf <= 0) {
            return response()->noContent();
        }

        $sharePercent = $publisher->publisherProfile?->custom_share_percent
            ?? (int) Setting::getValue('publisher_share_percent', 70);
        $publisherEarning = (int) floor($viewCostXaf * ($sharePercent / 100));
        $platformFee = $viewCostXaf - $publisherEarning;
        $deviceType = (string) ($payload['device_type'] ?? 'desktop');
        $adZoneId = isset($payload['ad_zone_id']) ? (int) $payload['ad_zone_id'] : null;
        $slotKey = $payload['slot_key'] ?? null;

        DB::transaction(function () use (
            $campaign,
            $publisher,
            $viewCostXaf,
            $publisherEarning,
            $platformFee,
            $ledger,
            $siteId,
            $adZoneId,
            $slotKey,
            $deviceType,
            $visitorHash,
            $request
        ): void {
            /** @var Campaign $lockedCampaign */
            $lockedCampaign = Campaign::query()->whereKey($campaign->id)->lockForUpdate()->firstOrFail();

            if ($lockedCampaign->status !== CampaignStatus::Active || $lockedCampaign->remainingBudgetXaf() < $viewCostXaf) {
                return;
            }

            $advWallet = $lockedCampaign->advertiser->wallet()->firstOrCreate(['user_id' => $lockedCampaign->advertiser_id]);
            if ($advWallet->available_balance_xaf < $viewCostXaf) {
                return;
            }

            $ledger->debitForAdSpend($advWallet, $viewCostXaf, $lockedCampaign, __('Vue unique au scroll sur :name', ['name' => $lockedCampaign->name]));

            $newSpent = $lockedCampaign->spent_xaf + $viewCostXaf;
            $updates = ['spent_xaf' => $newSpent];
            if ($newSpent >= $lockedCampaign->total_budget_xaf) {
                $updates['status'] = CampaignStatus::Completed;
            }
            $lockedCampaign->update($updates);

            if ($publisherEarning > 0) {
                $pubWallet = $publisher->wallet()->firstOrCreate(['user_id' => $publisher->id]);
                $ledger->creditPendingPublisherEarning($pubWallet, $publisherEarning, $lockedCampaign, __('Vue unique au scroll'));
            }

            // Marque l'impression comme facturée (ou la crée si absente)
            $existingImp = Impression::query()
                ->where('campaign_id', $lockedCampaign->id)
                ->where('publisher_id', $publisher->id)
                ->where('visitor_hash', $visitorHash)
                ->when($siteId > 0, fn ($q) => $q->where('site_id', $siteId))
                ->latest('created_at')
                ->first();

            if ($existingImp) {
                $existingImp->update(['cost_xaf' => $viewCostXaf]);
            } else {
                $creativeId = $lockedCampaign->creatives()->value('id');
                if ($creativeId) {
                    Impression::query()->create([
                        'campaign_id' => $lockedCampaign->id,
                        'creative_id' => $creativeId,
                        'site_id' => $siteId > 0 ? $siteId : null,
                        'ad_zone_id' => $adZoneId,
                        'publisher_id' => $publisher->id,
                        'visitor_hash' => $visitorHash,
                        'ip_address' => (string) $request->ip(),
                        'device_type' => $deviceType,
                        'cost_xaf' => $viewCostXaf,
                        'created_at' => now(),
                    ]);
                }
            }

            StatsDaily::recordRealTime(
                campaignId: $lockedCampaign->id,
                publisherId: $publisher->id,
                siteId: $siteId > 0 ? $siteId : null,
                adZoneId: $adZoneId,
                deviceType: $deviceType,
                increments: [
                    'spend_xaf' => $viewCostXaf,
                    'publisher_earning_xaf' => $publisherEarning,
                    'platform_fee_xaf' => $platformFee,
                ]
            );

            if ($siteId > 0 && $slotKey && $publisherEarning > 0) {
                $slotRow = AdSlotMetric::query()
                    ->where('site_id', $siteId)
                    ->where('slot_key', $slotKey)
                    ->where('device_type', $deviceType)
                    ->first();
                $slotRow?->increment('earnings_xaf', $publisherEarning);
            }
        });

        return response()->noContent();
    }
}
