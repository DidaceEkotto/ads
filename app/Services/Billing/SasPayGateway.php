<?php

namespace App\Services\Billing;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Implémentation de l'agrégateur SasPay (saspay.me) pour le marché camerounais.
 * Documentation : https://docs.saspay.me/quickstart#checkout-h%C3%A9berg%C3%A9
 */
class SasPayGateway implements PaymentGatewayInterface
{
    private const WEBHOOK_TOLERANCE_SECONDS = 300;

    public function createHostedCheckout(
        Transaction $transaction,
        User $user,
        string $returnUrl,
        string $webhookUrl
    ): array {
        $baseUrl = rtrim((string) config('saspay.base_url'), '/');
        $apiKey = (string) config('saspay.api_key');

        // SasPay exige les montants en chaîne décimale (ex: "5000.00") et non en float.
        $formattedAmount = number_format($transaction->amount_xaf, 2, '.', '');

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(15)
            ->post("{$baseUrl}/checkout/sessions", [
                'amount' => $formattedAmount,
                'currency' => 'XAF',
                'reference' => $transaction->reference,
                'description' => $transaction->description ?? 'Recharge portefeuille CM-Ads',
                'customer' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                'return_url' => $returnUrl,
                'webhook_url' => $webhookUrl,
                'metadata' => [
                    'transaction_id' => $transaction->id,
                    'transaction_reference' => $transaction->reference,
                    'wallet_id' => $transaction->wallet_id,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                __('Échec lors de la création de la session SasPay : :status', [
                    'status' => $response->status(),
                ])
            );
        }

        $data = $response->json('data') ?? $response->json();
        $checkoutUrl = $data['checkout_url'] ?? $data['url'] ?? null;

        if (! $checkoutUrl) {
            throw new RuntimeException(__('URL de checkout SasPay manquante dans la réponse.'));
        }

        return [
            'checkout_url' => $checkoutUrl,
            'gateway_reference' => $data['id'] ?? $data['session_id'] ?? $data['reference'] ?? null,
        ];
    }

    /**
     * Vérifie la signature HMAC-SHA256 sur "{timestamp}.{rawBody}" et bloque le rejeu > 5 min.
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        $secret = (string) config('saspay.webhook_secret');
        if ($secret === '') {
            return false;
        }

        $signature = (string) $request->header('X-Webhook-Signature', '');
        $timestamp = (string) $request->header('X-Webhook-Timestamp', '');

        if ($signature === '' || $timestamp === '' || ! ctype_digit($timestamp)) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > self::WEBHOOK_TOLERANCE_SECONDS) {
            return false;
        }

        $rawBody = $request->getContent();
        $expectedSignature = hash_hmac('sha256', "{$timestamp}.{$rawBody}", $secret);

        return hash_equals(strtolower($expectedSignature), strtolower($signature));
    }

    public function parseWebhookPayload(array $payload, ?string $eventHeader = null): array
    {
        $data = $payload['data'] ?? $payload;
        $event = $eventHeader ?: ($payload['event'] ?? '');
        $metadata = $data['metadata'] ?? [];

        $rawAmount = $data['amount'] ?? null;
        $amountXaf = $rawAmount !== null ? (int) round((float) $rawAmount) : null;

        $status = match ($event) {
            'transaction.success', 'checkout.completed', 'payment.succeeded' => 'completed',
            'transaction.failed', 'payment.failed' => 'failed',
            'transaction.cancelled', 'checkout.expired' => 'cancelled',
            default => (string) ($data['status'] ?? 'pending'),
        };

        return [
            'event' => (string) $event,
            'status' => $status,
            'transaction_reference' => $metadata['transaction_reference'] ?? $data['merchant_reference'] ?? $data['reference'] ?? null,
            'gateway_reference' => $data['id'] ?? $data['transaction_id'] ?? null,
            'amount_xaf' => $amountXaf,
        ];
    }

    public function initiatePayout(
        string $idempotencyKey,
        int $amountXaf,
        string $provider,
        string $accountNumber,
        string $accountName
    ): array {
        $baseUrl = rtrim((string) config('saspay.base_url'), '/');
        $apiKey = (string) config('saspay.api_key');

        $response = Http::withToken($apiKey)
            ->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->acceptJson()
            ->timeout(15)
            ->post("{$baseUrl}/payouts", [
                'amount' => number_format($amountXaf, 2, '.', ''),
                'currency' => 'XAF',
                'channel' => $provider,
                'recipient' => [
                    'phone' => $accountNumber,
                    'name' => $accountName,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(__('Échec du transfert SasPay : :status', ['status' => $response->status()]));
        }

        $data = $response->json('data') ?? $response->json();

        return [
            'gateway_reference' => $data['id'] ?? $data['reference'] ?? null,
            'status' => (string) ($data['status'] ?? 'processing'),
        ];
    }
}
