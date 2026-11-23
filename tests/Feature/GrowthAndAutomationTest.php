<?php

namespace Tests\Feature;

use App\Enums\BillingModel;
use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\PayoutStatus;
use App\Enums\SiteStatus;
use App\Enums\UserRole;
use App\Events\CampaignBudgetLow;
use App\Livewire\Admin\PayoutManager;
use App\Models\Campaign;
use App\Models\Click;
use App\Models\PaymentMethod;
use App\Models\Payout;
use App\Models\Site;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\CampaignBudgetLowNotification;
use App\Services\Billing\LedgerService;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class GrowthAndAutomationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
    }

    public function test_low_budget_event_notifies_advertiser_once_per_24h(): void
    {
        Notification::fake();

        $adv = User::query()->create([
            'name' => 'Adv Alert',
            'email' => 'alert@cm.cm',
            'phone' => '+237677000101',
            'role' => UserRole::Advertiser,
            'password' => 'Secret@1234',
        ]);

        $campaign = Campaign::query()->create([
            'advertiser_id' => $adv->id,
            'name' => 'Campagne Alerte',
            'type' => CampaignType::Web,
            'billing_model' => BillingModel::Cpc,
            'status' => CampaignStatus::Active,
            'total_budget_xaf' => 10000,
            'spent_xaf' => 8500,
            'unit_price_xaf' => 50,
        ]);

        CampaignBudgetLow::dispatch($campaign);
        CampaignBudgetLow::dispatch($campaign); // 2e appel dédupliqué

        Notification::assertSentToTimes($adv, CampaignBudgetLowNotification::class, 1);
    }

    public function test_referral_bonus_and_deposit_invoice(): void
    {
        $sponsor = User::query()->create([
            'name' => 'Parrain',
            'email' => 'parrain@cm.cm',
            'phone' => '+237699000101',
            'role' => UserRole::Publisher,
            'password' => 'Secret@1234',
        ]);
        $sponsorWallet = Wallet::query()->create(['user_id' => $sponsor->id, 'available_balance_xaf' => 0]);

        // Inscription du filleul avec le code du parrain
        $this->post('/register', [
            'name' => 'Filleul Adv',
            'email' => 'filleul@cm.cm',
            'phone' => '+237677000102',
            'role' => UserRole::Advertiser->value,
            'password' => 'Secret@1234',
            'password_confirmation' => 'Secret@1234',
            'referral_code' => $sponsor->referral_code,
            'accept_terms' => true,
        ])->assertRedirect();

        $filleul = User::query()->where('email', 'filleul@cm.cm')->firstOrFail();
        $this->assertSame($sponsor->id, $filleul->referred_by);

        $ledger = app(LedgerService::class);
        $tx = $ledger->createOrGetPendingDeposit($filleul->wallet, 10000, (string) Str::uuid(), 'saspay');
        $ledger->confirmDepositIdempotently($tx->reference, 'sp_ref_01', 10000);

        // 5% de 10 000 = 500 FCFA crédités au parrain
        $this->assertSame(500, $sponsorWallet->fresh()->available_balance_xaf);

        // L'annonceur peut ouvrir sa facture
        $this->actingAs($filleul)
            ->get("/advertiser/wallet/transactions/{$tx->id}/invoice")
            ->assertOk()
            ->assertSee($tx->reference);
    }

    public function test_conversion_pixel_first_party_cdn_and_auto_saspay_payout(): void
    {
        Http::fake([
            '*/payouts' => Http::response([
                'data' => ['id' => 'sp_payout_777', 'status' => 'processing'],
            ], 200),
        ]);

        $admin = User::query()->create([
            'name' => 'Admin Auto',
            'email' => 'adminauto@cm-ads.cm',
            'phone' => '+237690000777',
            'role' => UserRole::Admin,
            'password' => 'Admin@1234',
        ]);

        $pub = User::query()->create([
            'name' => 'Pub Auto Pay',
            'email' => 'pubautopay@cm.cm',
            'phone' => '+237699000777',
            'role' => UserRole::Publisher,
            'password' => 'Secret@1234',
        ]);
        Wallet::query()->create(['user_id' => $pub->id, 'available_balance_xaf' => 5000]);

        $site = Site::query()->create([
            'publisher_id' => $pub->id,
            'name' => 'First Party Site',
            'domain' => 'fp.cm',
            'category' => 'tech',
            'language' => 'fr',
            'status' => SiteStatus::Approved,
            'site_token' => 'fptoken123456',
            'custom_cdn_host' => 'https://media.fp.cm',
        ]);
        $this->assertStringContainsString('https://media.fp.cm/a/fptoken123456.js', $site->autoAdsHeadSnippet());

        // Pixel de conversion CPA
        $hash = hash('sha256', '127.0.0.1|Symfony');
        Click::query()->create([
            'campaign_id' => 99,
            'publisher_id' => $pub->id,
            'visitor_hash' => $hash,
            'ip_address' => '127.0.0.1',
            'device_type' => 'desktop',
            'is_valid' => true,
            'created_at' => now(),
        ]);

        $this->withHeaders(['User-Agent' => 'Symfony'])
            ->get('/m/conv/99?event=purchase&value=15000')
            ->assertOk();

        $this->assertDatabaseHas('conversions', [
            'campaign_id' => 99,
            'event_name' => 'purchase',
            'value_xaf' => 15000,
        ]);

        // Paiement automatique SasPay depuis l'Admin
        $method = PaymentMethod::query()->create([
            'user_id' => $pub->id,
            'provider' => 'mtn_momo',
            'account_number' => '+237677000777',
            'account_name' => 'Pub Auto Pay',
        ]);
        $payout = Payout::query()->create([
            'reference' => (string) Str::uuid(),
            'publisher_id' => $pub->id,
            'payment_method_id' => $method->id,
            'amount_xaf' => 5000,
            'status' => PayoutStatus::Pending,
        ]);

        $this->actingAs($admin);
        Livewire::test(PayoutManager::class)->call('payViaSasPay', $payout->id);

        $this->assertSame(PayoutStatus::Paid, $payout->fresh()->status);
        $this->assertSame('sp_payout_777', $payout->fresh()->gateway_reference);
    }
}
