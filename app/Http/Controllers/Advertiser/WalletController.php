<?php

namespace App\Http\Controllers\Advertiser;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\StoreDepositRequest;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\Billing\LedgerService;
use App\Services\Billing\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class WalletController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $wallet = $request->user()->wallet()->firstOrCreate(['user_id' => $request->user()->id]);

        $transactions = $wallet->transactions()
            ->latest()
            ->paginate(15)
            ->through(fn ($tx) => [
                'id' => $tx->id,
                'reference' => $tx->reference,
                'type' => $tx->type->value,
                'type_label' => $tx->type->label(),
                'direction' => $tx->direction,
                'amount_xaf' => $tx->amount_xaf,
                'balance_after_xaf' => $tx->balance_after_xaf,
                'status' => $tx->status,
                'gateway' => $tx->gateway,
                'description' => $tx->description,
                'invoice_url' => ($tx->type === TransactionType::Deposit && $tx->status === 'completed')
                    ? route('advertiser.wallet.invoice', $tx)
                    : null,
                'created_at' => $tx->created_at?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Advertiser/Wallet/Index', [
            'wallet' => [
                'available_balance_xaf' => $wallet->available_balance_xaf,
                'total_spent_xaf' => $wallet->total_spent_xaf,
            ],
            'transactions' => $transactions,
            'minDepositXaf' => (int) Setting::getValue('min_deposit_xaf', 2000),
            'initialIdempotencyKey' => (string) Str::uuid(),
        ]);
    }

    /**
     * Initialise une recharge de solde via Checkout Hébergé SasPay (avec idempotence applicative).
     */
    public function deposit(
        StoreDepositRequest $request,
        LedgerService $ledger,
        PaymentGatewayInterface $gateway
    ): Response {
        $wallet = $request->user()->wallet()->firstOrCreate(['user_id' => $request->user()->id]);

        try {
            $transaction = $ledger->createOrGetPendingDeposit(
                wallet: $wallet,
                amountXaf: (int) $request->validated('amount_xaf'),
                idempotencyKey: (string) $request->validated('idempotency_key'),
                gateway: 'saspay'
            );

            // Si déjà finalisée (retry tardif), on informe sans recréer de session.
            if ($transaction->status === 'completed') {
                return redirect()->route('advertiser.wallet.index')
                    ->with('success', __('Cette recharge a déjà été confirmée.'));
            }

            $session = $gateway->createHostedCheckout(
                transaction: $transaction,
                user: $request->user(),
                returnUrl: route('advertiser.wallet.callback', ['reference' => $transaction->reference]),
                webhookUrl: route('webhooks.saspay')
            );

            $transaction->update([
                'gateway_reference' => $session['gateway_reference'],
            ]);

            return Inertia::location($session['checkout_url']);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Page de retour navigateur après le Checkout Hébergé SasPay.
     * Note : la confirmation financière réelle se fait uniquement via le Webhook signé.
     */
    public function callback(Request $request)
    {
        return redirect()->route('advertiser.wallet.index')
            ->with('success', __('Paiement soumis. Votre solde sera crédité dès confirmation par SasPay.'));
    }

    /**
     * Affiche la facture imprimable d'une recharge confirmée.
     */
    public function invoice(Request $request, Transaction $transaction): HttpResponse
    {
        $wallet = $request->user()->wallet;
        if (! $wallet || $transaction->wallet_id !== $wallet->id || $transaction->status !== 'completed') {
            abort(403);
        }

        return response()->view('advertiser.deposit_invoice', [
            'transaction' => $transaction,
            'user' => $request->user(),
            'siteName' => (string) Setting::getValue('site_name', 'CM-Ads'),
        ]);
    }
}
