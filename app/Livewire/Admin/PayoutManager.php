<?php

namespace App\Livewire\Admin;

use App\Models\Payout;
use App\Notifications\PayoutApprovedNotification;
use App\Services\Billing\LedgerService;
use App\Services\Billing\PaymentGatewayInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('layouts.admin')]
#[Title('Gestion des retraits — CM-Ads Admin')]
class PayoutManager extends Component
{
    use WithFileUploads;

    public ?int $selectedPayoutId = null;

    public string $gatewayReference = '';

    public string $adminNote = '';

    public $proofImage = null;

    public function openApproveModal(int $payoutId): void
    {
        $this->reset(['gatewayReference', 'adminNote', 'proofImage']);
        $this->selectedPayoutId = $payoutId;
    }

    public function approve(LedgerService $ledger): void
    {
        if (! $this->selectedPayoutId) {
            return;
        }

        $this->validate([
            'gatewayReference' => ['nullable', 'string', 'max:100'],
            'adminNote' => ['nullable', 'string', 'max:500'],
            'proofImage' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $proofPath = null;
        if ($this->proofImage) {
            $ext = $this->proofImage->guessExtension() ?: 'png';
            $proofPath = $this->proofImage->storeAs('payout-proofs', Str::uuid().'.'.$ext, 'public');
        }

        $payout = Payout::query()->with('publisher')->findOrFail($this->selectedPayoutId);

        $updated = $ledger->processPayoutByAdmin(
            payout: $payout,
            admin: Auth::user(),
            approve: true,
            note: $this->adminNote ?: 'Payé manuellement via Mobile Money',
            gatewayRef: $this->gatewayReference ?: null,
            proofPath: $proofPath
        );

        $payout->publisher?->notify(new PayoutApprovedNotification($updated));

        $this->reset(['selectedPayoutId', 'gatewayReference', 'adminNote', 'proofImage']);
        session()->flash('success', __('Retrait validé et preuve envoyée à l\'éditeur.'));
    }

    /**
     * Déclenche un transfert automatique et idempotent via SasPay.
     */
    public function payViaSasPay(int $payoutId, PaymentGatewayInterface $gateway, LedgerService $ledger): void
    {
        $payout = Payout::query()->with(['publisher', 'paymentMethod'])->findOrFail($payoutId);
        if (! $payout->paymentMethod) {
            return;
        }

        try {
            $result = $gateway->initiatePayout(
                idempotencyKey: 'payout_'.$payout->reference,
                amountXaf: $payout->amount_xaf,
                provider: $payout->paymentMethod->provider,
                accountNumber: $payout->paymentMethod->account_number,
                accountName: $payout->paymentMethod->account_name
            );

            $updated = $ledger->processPayoutByAdmin(
                payout: $payout,
                admin: Auth::user(),
                approve: true,
                note: 'Transfert automatique via SasPay',
                gatewayRef: $result['gateway_reference']
            );

            $payout->publisher?->notify(new PayoutApprovedNotification($updated));
            session()->flash('success', __('Transfert automatique SasPay exécuté avec succès.'));
        } catch (Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function reject(int $payoutId, LedgerService $ledger): void
    {
        $payout = Payout::query()->findOrFail($payoutId);
        $ledger->processPayoutByAdmin(
            payout: $payout,
            admin: Auth::user(),
            approve: false,
            note: 'Rejeté par l\'administrateur'
        );
        session()->flash('success', __('Retrait rejeté et montant recrédité à l\'éditeur.'));
    }

    public function render()
    {
        return view('livewire.admin.payout-manager', [
            'payouts' => Payout::query()->with(['publisher', 'paymentMethod'])->latest()->take(50)->get(),
        ]);
    }
}
