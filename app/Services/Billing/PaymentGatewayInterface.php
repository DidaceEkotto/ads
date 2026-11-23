<?php

namespace App\Services\Billing;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Contrat d'abstraction pour les agrégateurs de paiement (SasPay, CinetPay, Campay...).
 */
interface PaymentGatewayInterface
{
    /**
     * Crée une session de paiement hébergée et retourne l'URL de redirection + la référence externe.
     *
     * @return array{checkout_url: string, gateway_reference: string|null}
     */
    public function createHostedCheckout(
        Transaction $transaction,
        User $user,
        string $returnUrl,
        string $webhookUrl
    ): array;

    /**
     * Vérifie l'authenticité et la fraîcheur (anti-replay) d'un webhook entrant.
     */
    public function verifyWebhookSignature(Request $request): bool;

    /**
     * Extrait les informations normalisées d'un payload webhook.
     *
     * @return array{
     *   event: string,
     *   status: string,
     *   transaction_reference: string|null,
     *   gateway_reference: string|null,
     *   amount_xaf: int|null
     * }
     */
    public function parseWebhookPayload(array $payload, ?string $eventHeader = null): array;

    /**
     * Initie un transfert sortant (payout) de manière idempotente.
     *
     * @return array{gateway_reference: string|null, status: string}
     */
    public function initiatePayout(
        string $idempotencyKey,
        int $amountXaf,
        string $provider,
        string $accountNumber,
        string $accountName
    ): array;
}
