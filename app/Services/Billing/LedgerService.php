<?php

namespace App\Services\Billing;

use App\Enums\PayoutStatus;
use App\Enums\TransactionType;
use App\Models\Click;
use App\Models\PaymentMethod;
use App\Models\Payout;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Service garantissant l'atomicité et l'idempotence de toutes les écritures financières (XAF).
 */
class LedgerService
{
    /**
     * Crée ou récupère de façon idempotente une transaction de recharge en attente.
     */
    public function createOrGetPendingDeposit(
        Wallet $wallet,
        int $amountXaf,
        string $idempotencyKey,
        string $gateway = 'saspay'
    ): Transaction {
        return DB::transaction(function () use ($wallet, $amountXaf, $idempotencyKey, $gateway): Transaction {
            $existing = Transaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ((int) $existing->amount_xaf !== $amountXaf || $existing->wallet_id !== $wallet->id) {
                    throw new RuntimeException(__('Conflit d\'idempotence : cette clé correspond à une autre opération.'));
                }

                return $existing;
            }

            /** @var Wallet $lockedWallet */
            $lockedWallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            return Transaction::query()->create([
                'reference' => (string) Str::uuid(),
                'idempotency_key' => $idempotencyKey,
                'wallet_id' => $lockedWallet->id,
                'type' => TransactionType::Deposit,
                'direction' => 'credit',
                'amount_xaf' => $amountXaf,
                'balance_after_xaf' => $lockedWallet->available_balance_xaf,
                'status' => 'pending',
                'gateway' => $gateway,
                'description' => __('Recharge de solde via :gateway', ['gateway' => strtoupper($gateway)]),
            ]);
        });
    }

    /**
     * Confirme un dépôt de façon idempotente (protégé contre les webhooks reçus plusieurs fois).
     */
    public function confirmDepositIdempotently(
        string $transactionReference,
        ?string $gatewayReference = null,
        ?int $paidAmountXaf = null
    ): Transaction {
        return DB::transaction(function () use ($transactionReference, $gatewayReference, $paidAmountXaf): Transaction {
            /** @var Transaction $transaction */
            $transaction = Transaction::query()
                ->where('reference', $transactionReference)
                ->lockForUpdate()
                ->firstOrFail();

            // Si déjà confirmée, on retourne immédiatement sans re-créditer (idempotence).
            if ($transaction->status === 'completed') {
                return $transaction;
            }

            if ($paidAmountXaf !== null && $paidAmountXaf !== (int) $transaction->amount_xaf) {
                throw new RuntimeException(__('Montant reçu différent du montant attendu.'));
            }

            /** @var Wallet $wallet */
            $wallet = Wallet::query()->whereKey($transaction->wallet_id)->lockForUpdate()->firstOrFail();
            $newBalance = $wallet->available_balance_xaf + $transaction->amount_xaf;

            $wallet->update(['available_balance_xaf' => $newBalance]);

            $transaction->update([
                'status' => 'completed',
                'balance_after_xaf' => $newBalance,
                'gateway_reference' => $gatewayReference ?? $transaction->gateway_reference,
            ]);

            // Bonus de parrainage automatique (ex: 5 % sur les recharges du filleul)
            $depositor = $wallet->user;
            if ($depositor && $depositor->referred_by) {
                $bonusPercent = (int) Setting::getValue('referral_bonus_percent', 5);
                $bonusXaf = (int) floor($transaction->amount_xaf * ($bonusPercent / 100));
                if ($bonusXaf > 0) {
                    $sponsorWallet = Wallet::query()->where('user_id', $depositor->referred_by)->lockForUpdate()->first();
                    if ($sponsorWallet) {
                        $newSponsorBalance = $sponsorWallet->available_balance_xaf + $bonusXaf;
                        $sponsorWallet->update([
                            'available_balance_xaf' => $newSponsorBalance,
                            'total_earned_xaf' => $sponsorWallet->total_earned_xaf + $bonusXaf,
                        ]);

                        Transaction::query()->create([
                            'reference' => (string) Str::uuid(),
                            'wallet_id' => $sponsorWallet->id,
                            'type' => TransactionType::Adjustment,
                            'direction' => 'credit',
                            'amount_xaf' => $bonusXaf,
                            'balance_after_xaf' => $newSponsorBalance,
                            'status' => 'completed',
                            'referenceable_type' => $transaction->getMorphClass(),
                            'referenceable_id' => $transaction->getKey(),
                            'description' => __('Bonus parrainage (:percent%)', ['percent' => $bonusPercent]),
                        ]);
                    }
                }
            }

            return $transaction;
        });
    }

    /**
     * Marque un dépôt en attente comme échoué ou annulé de façon idempotente.
     */
    public function markDepositFailed(string $transactionReference, string $status = 'failed', ?string $gatewayReference = null): Transaction
    {
        return DB::transaction(function () use ($transactionReference, $status, $gatewayReference): Transaction {
            /** @var Transaction $transaction */
            $transaction = Transaction::query()
                ->where('reference', $transactionReference)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaction->status === 'completed') {
                return $transaction;
            }

            $transaction->update([
                'status' => $status,
                'gateway_reference' => $gatewayReference ?? $transaction->gateway_reference,
            ]);

            return $transaction;
        });
    }

    /**
     * Débite le portefeuille d'un annonceur pour une dépense publicitaire (CPC / CPM / Forfait).
     */
    public function debitForAdSpend(Wallet $wallet, int $amountXaf, ?Model $referenceable = null, ?string $description = null): Transaction
    {
        return DB::transaction(function () use ($wallet, $amountXaf, $referenceable, $description): Transaction {
            /** @var Wallet $lockedWallet */
            $lockedWallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            if ($lockedWallet->available_balance_xaf < $amountXaf) {
                throw new RuntimeException(__('Solde annonceur insuffisant.'));
            }

            $newBalance = $lockedWallet->available_balance_xaf - $amountXaf;

            $lockedWallet->update([
                'available_balance_xaf' => $newBalance,
                'total_spent_xaf' => $lockedWallet->total_spent_xaf + $amountXaf,
            ]);

            return Transaction::query()->create([
                'reference' => (string) Str::uuid(),
                'wallet_id' => $lockedWallet->id,
                'type' => TransactionType::AdSpend,
                'direction' => 'debit',
                'amount_xaf' => $amountXaf,
                'balance_after_xaf' => $newBalance,
                'status' => 'completed',
                'referenceable_type' => $referenceable?->getMorphClass(),
                'referenceable_id' => $referenceable?->getKey(),
                'description' => $description ?? __('Dépense publicitaire'),
            ]);
        });
    }

    /**
     * Crédite le solde en attente (7-30j) d'un éditeur suite à un clic/impression valide.
     */
    public function creditPendingPublisherEarning(Wallet $wallet, int $amountXaf, ?Model $referenceable = null, ?string $description = null): Transaction
    {
        return DB::transaction(function () use ($wallet, $amountXaf, $referenceable, $description): Transaction {
            /** @var Wallet $lockedWallet */
            $lockedWallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            $lockedWallet->update([
                'pending_balance_xaf' => $lockedWallet->pending_balance_xaf + $amountXaf,
                'total_earned_xaf' => $lockedWallet->total_earned_xaf + $amountXaf,
            ]);

            return Transaction::query()->create([
                'reference' => (string) Str::uuid(),
                'wallet_id' => $lockedWallet->id,
                'type' => TransactionType::PublisherEarning,
                'direction' => 'credit',
                'amount_xaf' => $amountXaf,
                'balance_after_xaf' => $lockedWallet->available_balance_xaf,
                'status' => 'pending',
                'referenceable_type' => $referenceable?->getMorphClass(),
                'referenceable_id' => $referenceable?->getKey(),
                'description' => $description ?? __('Gain éditeur en attente de validation'),
            ]);
        });
    }

    /**
     * Débloque les gains éditeur arrivés à maturité (available_at <= now).
     */
    public function releaseMatureEarnings(): int
    {
        $releasedTotal = 0;

        $publisherIds = Click::query()
            ->where('is_valid', true)
            ->where('earning_status', 'pending')
            ->where('available_at', '<=', now())
            ->distinct()
            ->pluck('publisher_id');

        foreach ($publisherIds as $publisherId) {
            $releasedTotal += DB::transaction(function () use ($publisherId): int {
                $clicks = Click::query()
                    ->where('publisher_id', $publisherId)
                    ->where('is_valid', true)
                    ->where('earning_status', 'pending')
                    ->where('available_at', '<=', now())
                    ->lockForUpdate()
                    ->get();

                $amount = (int) $clicks->sum('publisher_earning_xaf');
                if ($amount <= 0) {
                    return 0;
                }

                /** @var Wallet $wallet */
                $wallet = Wallet::query()->where('user_id', $publisherId)->lockForUpdate()->firstOrFail();
                $deductPending = min($wallet->pending_balance_xaf, $amount);

                $wallet->update([
                    'pending_balance_xaf' => $wallet->pending_balance_xaf - $deductPending,
                    'available_balance_xaf' => $wallet->available_balance_xaf + $amount,
                ]);

                Click::query()->whereIn('id', $clicks->pluck('id'))->update(['earning_status' => 'available']);

                return $amount;
            });
        }

        return $releasedTotal;
    }

    /**
     * Enregistre une demande de retrait éditeur et réserve le montant sur le solde disponible.
     */
    public function requestPayout(User $publisher, PaymentMethod $paymentMethod, int $amountXaf): Payout
    {
        $minPayout = (int) Setting::getValue('min_payout_xaf', 5000);
        if ($amountXaf < $minPayout) {
            throw new RuntimeException(__('Le retrait minimum est de :min FCFA.', ['min' => $minPayout]));
        }

        return DB::transaction(function () use ($publisher, $paymentMethod, $amountXaf): Payout {
            /** @var Wallet $wallet */
            $wallet = Wallet::query()->where('user_id', $publisher->id)->lockForUpdate()->firstOrFail();

            if ($wallet->available_balance_xaf < $amountXaf) {
                throw new RuntimeException(__('Solde disponible insuffisant pour ce retrait.'));
            }

            $newBalance = $wallet->available_balance_xaf - $amountXaf;
            $wallet->update(['available_balance_xaf' => $newBalance]);

            $payout = Payout::query()->create([
                'reference' => (string) Str::uuid(),
                'publisher_id' => $publisher->id,
                'payment_method_id' => $paymentMethod->id,
                'amount_xaf' => $amountXaf,
                'status' => PayoutStatus::Pending,
            ]);

            Transaction::query()->create([
                'reference' => (string) Str::uuid(),
                'wallet_id' => $wallet->id,
                'type' => TransactionType::Payout,
                'direction' => 'debit',
                'amount_xaf' => $amountXaf,
                'balance_after_xaf' => $newBalance,
                'status' => 'pending',
                'referenceable_type' => $payout->getMorphClass(),
                'referenceable_id' => $payout->getKey(),
                'description' => __('Demande de retrait (:provider)', ['provider' => $paymentMethod->providerLabel()]),
            ]);

            return $payout;
        });
    }

    /**
     * Valide un retrait (mode manuel admin) avec preuve éventuelle ou le rejette avec remboursement atomique.
     */
    public function processPayoutByAdmin(
        Payout $payout,
        User $admin,
        bool $approve,
        ?string $note = null,
        ?string $gatewayRef = null,
        ?string $proofPath = null
    ): Payout {
        return DB::transaction(function () use ($payout, $admin, $approve, $note, $gatewayRef, $proofPath): Payout {
            /** @var Payout $lockedPayout */
            $lockedPayout = Payout::query()->whereKey($payout->id)->lockForUpdate()->firstOrFail();

            if ($lockedPayout->status !== PayoutStatus::Pending) {
                throw new RuntimeException(__('Cette demande de retrait a déjà été traitée.'));
            }

            if ($approve) {
                $lockedPayout->update([
                    'status' => PayoutStatus::Paid,
                    'admin_note' => $note,
                    'gateway_reference' => $gatewayRef,
                    'proof_path' => $proofPath,
                    'processed_by' => $admin->id,
                    'processed_at' => now(),
                ]);

                Transaction::query()
                    ->where('referenceable_type', $lockedPayout->getMorphClass())
                    ->where('referenceable_id', $lockedPayout->getKey())
                    ->update(['status' => 'completed', 'gateway_reference' => $gatewayRef]);

                return $lockedPayout;
            }

            /** @var Wallet $wallet */
            $wallet = Wallet::query()->where('user_id', $lockedPayout->publisher_id)->lockForUpdate()->firstOrFail();
            $newBalance = $wallet->available_balance_xaf + $lockedPayout->amount_xaf;
            $wallet->update(['available_balance_xaf' => $newBalance]);

            $lockedPayout->update([
                'status' => PayoutStatus::Rejected,
                'admin_note' => $note,
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            Transaction::query()->create([
                'reference' => (string) Str::uuid(),
                'wallet_id' => $wallet->id,
                'type' => TransactionType::Refund,
                'direction' => 'credit',
                'amount_xaf' => $lockedPayout->amount_xaf,
                'balance_after_xaf' => $newBalance,
                'status' => 'completed',
                'referenceable_type' => $lockedPayout->getMorphClass(),
                'referenceable_id' => $lockedPayout->getKey(),
                'description' => __('Remboursement retrait rejeté'),
            ]);

            return $lockedPayout;
        });
    }
}
