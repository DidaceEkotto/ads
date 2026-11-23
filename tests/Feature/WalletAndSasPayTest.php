<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Billing\LedgerService;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class WalletAndSasPayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);

        Config::set('saspay.base_url', 'https://api.saspay.me/api/v1');
        Config::set('saspay.api_key', 'sk_test_123456');
        Config::set('saspay.webhook_secret', 'whsec_test_secret');
    }

    public function test_advertiser_can_initiate_saspay_hosted_checkout_and_enforces_idempotency(): void
    {
        Http::fake([
            'https://api.saspay.me/api/v1/checkout/sessions' => Http::response([
                'data' => [
                    'id' => 'cs_saspay_001',
                    'checkout_url' => 'https://checkout.saspay.me/pay/cs_saspay_001',
                ],
            ], 201),
        ]);

        $advertiser = User::query()->create([
            'name' => 'Annonceur Test',
            'email' => 'adv@cm.cm',
            'phone' => '+237677000111',
            'role' => UserRole::Advertiser,
            'password' => 'Secret1234',
        ]);
        $wallet = Wallet::query()->create(['user_id' => $advertiser->id]);

        $idempotencyKey = (string) Str::uuid();

        $response = $this->actingAs($advertiser)->post('/advertiser/wallet/deposit', [
            'amount_xaf' => 10000,
            'idempotency_key' => $idempotencyKey,
        ]);

        // Inertia::location() retourne 302 en requête classique ou 409 X-Inertia-Location en XHR
        $response->assertRedirect('https://checkout.saspay.me/pay/cs_saspay_001');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.saspay.me/api/v1/checkout/sessions'
                && $request['amount'] === '10000.00'
                && $request['currency'] === 'XAF';
        });

        $this->assertDatabaseCount('transactions', 1);

        // Appel idempotent côté LedgerService avec la même clé : aucune ligne dupliquée
        $ledger = app(LedgerService::class);
        $sameTx = $ledger->createOrGetPendingDeposit($wallet, 10000, $idempotencyKey, 'saspay');
        $this->assertSame(1, Transaction::query()->count());
        $this->assertSame('pending', $sameTx->status);
    }

    public function test_saspay_webhook_credits_wallet_idempotently_and_rejects_bad_signatures(): void
    {
        $advertiser = User::query()->create([
            'name' => 'Annonceur Webhook',
            'email' => 'wh@cm.cm',
            'phone' => '+237677000222',
            'role' => UserRole::Advertiser,
            'password' => 'Secret1234',
        ]);
        $wallet = Wallet::query()->create(['user_id' => $advertiser->id]);

        $ledger = app(LedgerService::class);
        $tx = $ledger->createOrGetPendingDeposit($wallet, 5000, (string) Str::uuid(), 'saspay');

        $payload = json_encode([
            'event' => 'transaction.success',
            'data' => [
                'id' => 'sp_tx_999',
                'amount' => '5000.00',
                'status' => 'completed',
                'metadata' => [
                    'transaction_reference' => $tx->reference,
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $timestamp = (string) time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", 'whsec_test_secret');

        // 1. Mauvaise signature -> 401
        $this->call('POST', '/webhooks/saspay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_WEBHOOK_SIGNATURE' => 'invalid_sig',
            'HTTP_X_WEBHOOK_TIMESTAMP' => $timestamp,
            'HTTP_X_WEBHOOK_EVENT' => 'transaction.success',
        ], $payload)->assertStatus(401);

        // 2. Timestamp expiré (> 300s) -> 401
        $oldTimestamp = (string) (time() - 600);
        $oldSig = hash_hmac('sha256', "{$oldTimestamp}.{$payload}", 'whsec_test_secret');
        $this->call('POST', '/webhooks/saspay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_WEBHOOK_SIGNATURE' => $oldSig,
            'HTTP_X_WEBHOOK_TIMESTAMP' => $oldTimestamp,
            'HTTP_X_WEBHOOK_EVENT' => 'transaction.success',
        ], $payload)->assertStatus(401);

        // 3. Webhook valide (1er envoi) -> crédite 5000 FCFA
        $this->call('POST', '/webhooks/saspay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
            'HTTP_X_WEBHOOK_TIMESTAMP' => $timestamp,
            'HTTP_X_WEBHOOK_EVENT' => 'transaction.success',
        ], $payload)->assertOk();

        $wallet->refresh();
        $this->assertSame(5000, $wallet->available_balance_xaf);

        // 4. Même webhook reçu une 2e fois (retry SasPay) -> idempotent, solde reste à 5000 FCFA
        $this->call('POST', '/webhooks/saspay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
            'HTTP_X_WEBHOOK_TIMESTAMP' => $timestamp,
            'HTTP_X_WEBHOOK_EVENT' => 'transaction.success',
        ], $payload)->assertOk();

        $wallet->refresh();
        $this->assertSame(5000, $wallet->available_balance_xaf);
    }

    public function test_publisher_can_manage_own_mobile_money_methods(): void
    {
        $pub1 = User::query()->create([
            'name' => 'Editeur 1',
            'email' => 'pub1@cm.cm',
            'phone' => '+237699111111',
            'role' => UserRole::Publisher,
            'password' => 'Secret1234',
        ]);
        $pub2 = User::query()->create([
            'name' => 'Editeur 2',
            'email' => 'pub2@cm.cm',
            'phone' => '+237699222222',
            'role' => UserRole::Publisher,
            'password' => 'Secret1234',
        ]);

        $this->actingAs($pub1)->post('/publisher/payment-methods', [
            'provider' => 'mtn_momo',
            'account_number' => '+237677889900',
            'account_name' => 'Editeur Un',
            'is_default' => true,
        ])->assertRedirect();

        $method = PaymentMethod::query()->firstOrFail();
        $this->assertSame($pub1->id, $method->user_id);
        $this->assertTrue($method->is_default);

        // Un autre éditeur ne peut pas supprimer ce compte
        $this->actingAs($pub2)
            ->delete("/publisher/payment-methods/{$method->id}")
            ->assertForbidden();
    }
}
