<?php

namespace App\Services\Automation;

use App\Enums\CampaignStatus;
use App\Jobs\AggregateDailyStatsJob;
use App\Models\Campaign;
use App\Services\Billing\LedgerService;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Exécute automatiquement les tâches planifiées (expiration campagnes,
 * agrégation stats, déblocage des gains) sans configuration crontab serveur.
 */
class DynamicCronService
{
    public function __construct(private readonly LedgerService $ledger) {}

    /**
     * @return array{expired_campaigns: int, stats_aggregated: bool, released_xaf: int}
     */
    public function runDueTasks(bool $force = false): array
    {
        $result = [
            'expired_campaigns' => 0,
            'stats_aggregated' => false,
            'released_xaf' => 0,
        ];

        $lock = Cache::lock('cm_ads_dynamic_cron_lock', 30);
        if (! $lock->get()) {
            return $result;
        }

        try {
            // 1. Clôture automatique des campagnes arrivées à échéance (toutes les 5 min)
            if ($force || Cache::add('cm_ads_cron_5m', now()->toIso8601String(), now()->addMinutes(5))) {
                $result['expired_campaigns'] = Campaign::query()
                    ->where('status', CampaignStatus::Active)
                    ->whereNotNull('ends_at')
                    ->where('ends_at', '<=', now())
                    ->update(['status' => CampaignStatus::Completed]);
            }

            // 2. Agrégation horaire des statistiques journalières
            if ($force || Cache::add('cm_ads_cron_hourly', now()->toIso8601String(), now()->addHour())) {
                AggregateDailyStatsJob::dispatchSync();
                $result['stats_aggregated'] = true;
            }

            // 3. Déblocage quotidien des gains éditeurs arrivés à maturité
            if ($force || Cache::add('cm_ads_cron_daily', now()->toDateString(), now()->endOfDay())) {
                $result['released_xaf'] = $this->ledger->releaseMatureEarnings();
            }

            Cache::put('cm_ads_cron_last_run', now()->toDateTimeString(), now()->addDays(7));
        } catch (Throwable) {
            // Ne jamais bloquer une requête web si une tâche de fond rencontre une erreur passagère
        } finally {
            $lock->release();
        }

        return $result;
    }
}
