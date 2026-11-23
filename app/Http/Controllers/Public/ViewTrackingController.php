<?php

namespace App\Http\Controllers\Public;

use App\Enums\BillingModel;
use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Setting;
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

        // Déduplication : 1 vue facturable max par visiteur / campagne toutes les 10 minutes
        $dedupKey = "view_dedup:{$campaignId}:{$visitorHash}";
        if (! Cache::add($dedupKey, 1, now()->addMinutes(10))) {
            return response()->noContent();
        }

        $campaign = Campaign::query()->with('advertiser.wallet')->find($campaignId);
        $publisher = User::query()->with(['wallet', 'publisherProfile'])->find($publisherId);

        if (! $campaign || ! $publisher || $campaign->billing_model !== BillingModel::Cpm) {
            return response()->noContent();
        }

        $viewCostXaf = (int) Setting::getValue('view_unit_price_xaf', 5);

        $sharePercent = $publisher->publisherProfile?->custom_share_percent
            ?? (int) Setting::getValue('publisher_share_percent', 70);
        $publisherEarning = (int) floor($viewCostXaf * ($sharePercent / 100));

        DB::transaction(function () use ($campaign, $publisher, $viewCostXaf, $publisherEarning, $ledger): void {
            /** @var Campaign $lockedCampaign */
            $lockedCampaign = Campaign::query()->whereKey($campaign->id)->lockForUpdate()->firstOrFail();

            if ($lockedCampaign->status !== CampaignStatus::Active || $lockedCampaign->remainingBudgetXaf() < $viewCostXaf) {
                return;
            }

            $advWallet = $lockedCampaign->advertiser->wallet()->firstOrCreate(['user_id' => $lockedCampaign->advertiser_id]);
            if ($advWallet->available_balance_xaf < $viewCostXaf) {
                return;
            }

            $ledger->debitForAdSpend($advWallet, $viewCostXaf, $lockedCampaign, __('Vue visible au scroll sur :name', ['name' => $lockedCampaign->name]));

            $newSpent = $lockedCampaign->spent_xaf + $viewCostXaf;
            $updates = ['spent_xaf' => $newSpent];
            if ($newSpent >= $lockedCampaign->total_budget_xaf) {
                $updates['status'] = CampaignStatus::Completed;
            }
            $lockedCampaign->update($updates);

            if ($publisherEarning > 0) {
                $pubWallet = $publisher->wallet()->firstOrCreate(['user_id' => $publisher->id]);
                $ledger->creditPendingPublisherEarning($pubWallet, $publisherEarning, $lockedCampaign, __('Vue visible au scroll'));
            }
        });

        return response()->noContent();
    }
}
