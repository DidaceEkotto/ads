<?php

namespace App\Jobs;

use App\Enums\BillingModel;
use App\Enums\CampaignStatus;
use App\Events\CampaignBudgetLow;
use App\Models\Campaign;
use App\Models\Click;
use App\Models\FraudFlag;
use App\Models\Setting;
use App\Models\StatsDaily;
use App\Models\User;
use App\Services\AntiFraud\ClickFraudDetector;
use App\Services\Billing\LedgerService;
use App\Services\Geo\GeoLocationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Traite un clic publicitaire : contrôle anti-fraude, facturation CPC et crédit éditeur en attente.
 */
class ProcessAdClickJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{
     *   campaign_id: int,
     *   creative_id: int|null,
     *   site_id: int|null,
     *   ad_zone_id: int|null,
     *   social_placement_id: int|null,
     *   tracking_link_id: int|null,
     *   publisher_id: int,
     *   visitor_hash: string,
     *   ip_address: string,
     *   user_agent: string|null,
     *   referer: string|null,
     *   city: string|null,
     *   device_type: string,
     *   authenticated_user_id: int|null
     * }  $data
     */
    public function __construct(public array $data) {}

    public function handle(ClickFraudDetector $detector, LedgerService $ledger, GeoLocationService $geo): void
    {
        $campaign = Campaign::query()->with('advertiser.wallet')->find($this->data['campaign_id']);
        $publisher = User::query()->with(['wallet', 'publisherProfile'])->find($this->data['publisher_id']);

        if (! $campaign || ! $publisher) {
            return;
        }

        $location = (! empty($this->data['country']) && ! empty($this->data['city']))
            ? ['country' => $this->data['country'], 'city' => $this->data['city']]
            : $geo->locate($this->data['ip_address'] ?? null);

        $verdict = $detector->evaluate(
            campaignId: $campaign->id,
            publisherId: $publisher->id,
            visitorHash: $this->data['visitor_hash'],
            ipAddress: $this->data['ip_address'],
            userAgent: $this->data['user_agent'],
            authenticatedUserId: $this->data['authenticated_user_id'] ?? null,
        );

        if (! $verdict['is_valid']) {
            $click = Click::query()->create([
                'campaign_id' => $campaign->id,
                'creative_id' => $this->data['creative_id'],
                'site_id' => $this->data['site_id'],
                'ad_zone_id' => $this->data['ad_zone_id'],
                'social_placement_id' => $this->data['social_placement_id'],
                'tracking_link_id' => $this->data['tracking_link_id'],
                'publisher_id' => $publisher->id,
                'visitor_hash' => $this->data['visitor_hash'],
                'ip_address' => $this->data['ip_address'],
                'user_agent' => mb_substr((string) $this->data['user_agent'], 0, 512),
                'referer' => $this->data['referer'] ? mb_substr($this->data['referer'], 0, 512) : null,
                'country' => $location['country'],
                'city' => $location['city'],
                'device_type' => $this->data['device_type'],
                'is_valid' => false,
                'fraud_score' => $verdict['fraud_score'],
                'invalid_reason' => $verdict['reason']?->value,
                'earning_status' => 'rejected',
                'created_at' => now(),
            ]);

            FraudFlag::query()->create([
                'click_id' => $click->id,
                'campaign_id' => $campaign->id,
                'publisher_id' => $publisher->id,
                'reason' => $verdict['reason'],
                'score' => $verdict['fraud_score'],
                'ip_address' => $this->data['ip_address'],
                'details' => [
                    'user_agent' => $this->data['user_agent'],
                    'visitor_hash' => $this->data['visitor_hash'],
                ],
            ]);

            StatsDaily::recordRealTime(
                campaignId: $campaign->id,
                publisherId: $publisher->id,
                siteId: isset($this->data['site_id']) ? (int) $this->data['site_id'] : null,
                adZoneId: isset($this->data['ad_zone_id']) ? (int) $this->data['ad_zone_id'] : null,
                deviceType: (string) ($this->data['device_type'] ?? 'desktop'),
                increments: ['invalid_clicks_count' => 1],
            );

            return;
        }

        // Facturation CPC pilotée par les paramètres Admin (entiers XAF)
        $costXaf = $campaign->billing_model === BillingModel::Cpc
            ? (int) Setting::getValue('cpc_unit_price_xaf', $campaign->unit_price_xaf)
            : 0;
        $sharePercent = $publisher->publisherProfile?->custom_share_percent
            ?? (int) Setting::getValue('publisher_share_percent', 70);
        $holdDays = (int) Setting::getValue('earnings_hold_days', 14);

        $publisherEarning = (int) floor($costXaf * ($sharePercent / 100));
        $platformFee = $costXaf - $publisherEarning;

        DB::transaction(function () use (
            $campaign,
            $publisher,
            $costXaf,
            $publisherEarning,
            $platformFee,
            $holdDays,
            $ledger,
            $location
        ): void {
            /** @var Campaign $lockedCampaign */
            $lockedCampaign = Campaign::query()->whereKey($campaign->id)->lockForUpdate()->firstOrFail();

            if ($lockedCampaign->status !== CampaignStatus::Active || $lockedCampaign->remainingBudgetXaf() < $costXaf) {
                return;
            }

            $click = Click::query()->create([
                'campaign_id' => $lockedCampaign->id,
                'creative_id' => $this->data['creative_id'],
                'site_id' => $this->data['site_id'],
                'ad_zone_id' => $this->data['ad_zone_id'],
                'social_placement_id' => $this->data['social_placement_id'],
                'tracking_link_id' => $this->data['tracking_link_id'],
                'publisher_id' => $publisher->id,
                'visitor_hash' => $this->data['visitor_hash'],
                'ip_address' => $this->data['ip_address'],
                'user_agent' => mb_substr((string) $this->data['user_agent'], 0, 512),
                'referer' => $this->data['referer'] ? mb_substr($this->data['referer'], 0, 512) : null,
                'country' => $location['country'],
                'city' => $location['city'],
                'device_type' => $this->data['device_type'],
                'is_valid' => true,
                'fraud_score' => 0,
                'cost_xaf' => $costXaf,
                'publisher_earning_xaf' => $publisherEarning,
                'platform_fee_xaf' => $platformFee,
                'earning_status' => 'pending',
                'available_at' => now()->addDays($holdDays),
                'created_at' => now(),
            ]);

            if ($costXaf > 0) {
                $advWallet = $lockedCampaign->advertiser->wallet()->firstOrCreate(['user_id' => $lockedCampaign->advertiser_id]);
                $ledger->debitForAdSpend($advWallet, $costXaf, $click, __('Clic valide sur :name', ['name' => $lockedCampaign->name]));

                $newSpent = $lockedCampaign->spent_xaf + $costXaf;
                $updates = ['spent_xaf' => $newSpent];

                if ($newSpent >= $lockedCampaign->total_budget_xaf) {
                    $updates['status'] = CampaignStatus::Completed;
                }

                $lockedCampaign->update($updates);

                // Alerte budget bas (< 20 % restant)
                $remaining = $lockedCampaign->total_budget_xaf - $newSpent;
                if ($remaining > 0 && $remaining <= (int) ($lockedCampaign->total_budget_xaf * 0.2)) {
                    CampaignBudgetLow::dispatch($lockedCampaign);
                }
            }

            if ($publisherEarning > 0) {
                $pubWallet = $publisher->wallet()->firstOrCreate(['user_id' => $publisher->id]);
                $ledger->creditPendingPublisherEarning($pubWallet, $publisherEarning, $click, __('Clic valide'));
            }

            StatsDaily::recordRealTime(
                campaignId: $lockedCampaign->id,
                publisherId: $publisher->id,
                siteId: isset($this->data['site_id']) ? (int) $this->data['site_id'] : null,
                adZoneId: isset($this->data['ad_zone_id']) ? (int) $this->data['ad_zone_id'] : null,
                deviceType: (string) ($this->data['device_type'] ?? 'desktop'),
                increments: [
                    'valid_clicks_count' => 1,
                    'spend_xaf' => $costXaf,
                    'publisher_earning_xaf' => $publisherEarning,
                    'platform_fee_xaf' => $platformFee,
                ],
            );
        });
    }
}
