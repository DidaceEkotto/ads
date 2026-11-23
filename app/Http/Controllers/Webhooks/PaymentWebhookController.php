<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Billing\LedgerService;
use App\Services\Billing\PaymentGatewayInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PaymentWebhookController extends Controller
{
    /**
     * Reçoit les notifications SasPay de manière sécurisée (HMAC + anti-replay) et idempotente.
     */
    public function saspay(
        Request $request,
        PaymentGatewayInterface $gateway,
        LedgerService $ledger
    ): JsonResponse {
        if (! $gateway->verifyWebhookSignature($request)) {
            return response()->json(['message' => 'Invalid signature or expired timestamp'], 401);
        }

        $parsed = $gateway->parseWebhookPayload(
            payload: $request->all(),
            eventHeader: $request->header('X-Webhook-Event')
        );

        $reference = $parsed['transaction_reference'];
        if (! $reference) {
            return response()->json(['message' => 'Missing transaction reference'], 422);
        }

        try {
            if ($parsed['status'] === 'completed') {
                $ledger->confirmDepositIdempotently(
                    transactionReference: $reference,
                    gatewayReference: $parsed['gateway_reference'],
                    paidAmountXaf: $parsed['amount_xaf']
                );
            } elseif (in_array($parsed['status'], ['failed', 'cancelled'], true)) {
                $ledger->markDepositFailed(
                    transactionReference: $reference,
                    status: $parsed['status'],
                    gatewayReference: $parsed['gateway_reference']
                );
            }

            return response()->json(['status' => 'ok']);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
