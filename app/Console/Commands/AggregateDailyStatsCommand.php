<?php

namespace App\Console\Commands;

use App\Jobs\AggregateDailyStatsJob;
use Illuminate\Console\Command;

class AggregateDailyStatsCommand extends Command
{
    protected $signature = 'stats:aggregate {--date= : Date YYYY-MM-DD à agréger (défaut : aujourd\'hui)}';

    protected $description = 'Agrège les impressions et clics dans la table stats_daily';

    public function handle(): int
    {
        $date = $this->option('date');
        AggregateDailyStatsJob::dispatchSync($date ? (string) $date : null);

        $this->info('Agrégation des statistiques terminée.');

        return self::SUCCESS;
    }
}
