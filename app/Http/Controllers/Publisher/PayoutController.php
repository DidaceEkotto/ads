<?php

namespace App\Http\Controllers\Publisher;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\Payout;
use App\Models\Setting;
use App\Services\Billing\LedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Throwable;

class PayoutController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        $wallet = $user->wallet()->firstOrCreate(['user_id' => $user->id]);

        $methods = $user->paymentMethods()->get()->map(fn (PaymentMethod $m) => [
            'id' => $m->id,
            'label' => $m->providerLabel().' — '.$m->account_number,
            'is_default' => $m->is_default,
        ]);

        $payouts = Payout::query()
            ->with('paymentMethod')
            ->where('publisher_id', $user->id)
            ->latest()
            ->paginate(15)
            ->through(fn (Payout $p) => [
                'id' => $p->id,
                'reference' => $p->reference,
                'amount_xaf' => $p->amount_xaf,
                'status' => $p->status->value,
                'status_label' => $p->status->label(),
                'method_label' => $p->paymentMethod?->providerLabel().' ('.$p->paymentMethod?->account_number.')',
                'gateway_reference' => $p->gateway_reference,
                'proof_url' => $p->proofUrl(),
                'receipt_url' => route('publisher.payouts.receipt', $p),
                'admin_note' => $p->admin_note,
                'created_at' => $p->created_at?->format('d/m/Y H:i'),
                'processed_at' => $p->processed_at?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Publisher/Payouts/Index', [
            'wallet' => [
                'available_balance_xaf' => $wallet->available_balance_xaf,
                'pending_balance_xaf' => $wallet->pending_balance_xaf,
            ],
            'methods' => $methods,
            'payouts' => $payouts,
            'minPayoutXaf' => (int) Setting::getValue('min_payout_xaf', 5000),
            'earningsHoldDays' => (int) Setting::getValue('earnings_hold_days', 14),
        ]);
    }

    public function store(Request $request, LedgerService $ledger): RedirectResponse
    {
        $minPayout = (int) Setting::getValue('min_payout_xaf', 5000);
        $validated = $request->validate([
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'amount_xaf' => ['required', 'integer', "min:{$minPayout}"],
        ]);

        /** @var PaymentMethod $method */
        $method = PaymentMethod::query()->findOrFail($validated['payment_method_id']);
        if ($method->user_id !== $request->user()->id) {
            abort(403);
        }

        try {
            $ledger->requestPayout($request->user(), $method, (int) $validated['amount_xaf']);

            return back()->with('success', __('Demande de retrait enregistrée. Le montant a été réservé sur votre solde.'));
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Affiche un reçu officiel imprimable / PDF pour un retrait éditeur.
     */
    public function receipt(Request $request, Payout $payout): Response
    {
        if ($payout->publisher_id !== $request->user()->id) {
            abort(403);
        }

        $payout->load(['publisher', 'paymentMethod']);

        return response()->view('publisher.payout_receipt', [
            'payout' => $payout,
        ]);
    }
}
