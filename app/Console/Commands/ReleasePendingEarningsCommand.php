<?php

namespace App\Console\Commands;

use App\Services\Billing\LedgerService;
use Illuminate\Console\Command;

class ReleasePendingEarningsCommand extends Command
{
    protected $signature = 'earnings:release';

    protected $description = 'Débloque les gains éditeurs arrivés à maturité vers le solde disponible';

    public function handle(LedgerService $ledger): int
    {
        $released = $ledger->releaseMatureEarnings();
        $this->info("Montant débloqué : {$released} FCFA.");

        return self::SUCCESS;
    }
}
