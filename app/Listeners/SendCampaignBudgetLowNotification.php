<?php

namespace App\Listeners;

use App\Events\CampaignBudgetLow;
use App\Notifications\CampaignBudgetLowNotification;
use Illuminate\Support\Facades\Cache;

class SendCampaignBudgetLowNotification
{
    public function handle(CampaignBudgetLow $event): void
    {
        $campaign = $event->campaign;
        $dedupKey = "low_budget_notified:{$campaign->id}";

        // Évite d'envoyer plusieurs alertes pour la même campagne en moins de 24h
        if (! Cache::add($dedupKey, 1, now()->addHours(24))) {
            return;
        }

        $campaign->advertiser?->notify(new CampaignBudgetLowNotification($campaign));
    }
}
