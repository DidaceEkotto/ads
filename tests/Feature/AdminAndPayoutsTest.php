<?php

namespace Tests\Feature;

use App\Enums\BillingModel;
use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\PayoutStatus;
use App\Enums\SiteStatus;
use App\Enums\UserRole;
use App\Livewire\Admin\PayoutManager;
use App\Livewire\Admin\PlatformSettings;
use App\Livewire\Admin\ValidationQueue;
use App\Models\Campaign;
use App\Models\Click;
use App\Models\PaymentMethod;
use App\Models\Payout;
use App\Models\Setting;
use App\Models\Site;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\PayoutApprovedNotification;
use App\Services\Billing\LedgerService;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAndPayoutsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
    }

    public function test_mature_earnings_release_and_publisher_payout_request(): void
    {
        $pub = User::query()->create([
            'name' => 'Editeur Retrait',
            'email' => 'pubpay@cm.cm',
            'phone' => '+237699777888',
            'role' => UserRole::Publisher,
            'password' => 'Secret1234',
        ]);
        $wallet = Wallet::query()->create([
            'user_id' => $pub->id,
            'pending_balance_xaf' => 6000,
            'available_balance_xaf' => 0,
        ]);

        Click::query()->create([
            'campaign_id' => 1,
            'publisher_id' => $pub->id,
            'visitor_hash' => str_repeat('b', 64),
            'ip_address' => '1.1.1.1',
            'device_type' => 'mobile',
            'is_valid' => true,
            'cost_xaf' => 8571,
            'publisher_earning_xaf' => 6000,
            'platform_fee_xaf' => 2571,
            'earning_status' => 'pending',
            'available_at' => now()->subMinute(),
            'created_at' => now()->subDays(15),
        ]);

        $released = app(LedgerService::class)->releaseMatureEarnings();
        $this->assertSame(6000, $released);
        $wallet->refresh();
        $this->assertSame(0, $wallet->pending_balance_xaf);
        $this->assertSame(6000, $wallet->available_balance_xaf);

        $method = PaymentMethod::query()->create([
            'user_id' => $pub->id,
            'provider' => 'mtn_momo',
            'account_number' => '+237677112233',
            'account_name' => 'Editeur Retrait',
            'is_default' => true,
        ]);

        $this->actingAs($pub)->post('/publisher/payouts', [
            'payment_method_id' => $method->id,
            'amount_xaf' => 5000,
        ])->assertRedirect();

        $this->assertSame(1000, $wallet->fresh()->available_balance_xaf);
        $this->assertDatabaseHas('payouts', [
            'publisher_id' => $pub->id,
            'amount_xaf' => 5000,
            'status' => PayoutStatus::Pending->value,
        ]);
    }

    public function test_admin_livewire_components_manage_validations_payouts_and_settings(): void
    {
        $admin = User::query()->create([
            'name' => 'Super Admin',
            'email' => 'super@cm-ads.cm',
            'phone' => '+237690000111',
            'role' => UserRole::Admin,
            'password' => 'Admin1234',
        ]);

        $pub = User::query()->create([
            'name' => 'Pub Admin',
            'email' => 'pubadm@cm.cm',
            'phone' => '+237699123123',
            'role' => UserRole::Publisher,
            'password' => 'Secret1234',
        ]);
        $pubWallet = Wallet::query()->create(['user_id' => $pub->id, 'available_balance_xaf' => 0]);

        $site = Site::query()->create([
            'publisher_id' => $pub->id,
            'name' => 'News CM',
            'domain' => 'newscm.cm',
            'category' => 'actualites',
            'language' => 'fr',
            'status' => SiteStatus::Pending,
        ]);

        $campaign = Campaign::query()->create([
            'advertiser_id' => $pub->id,
            'name' => 'Campagne Test',
            'type' => CampaignType::Web,
            'billing_model' => BillingModel::Cpc,
            'status' => CampaignStatus::PendingReview,
            'total_budget_xaf' => 10000,
            'unit_price_xaf' => 50,
        ]);

        $method = PaymentMethod::query()->create([
            'user_id' => $pub->id,
            'provider' => 'orange_money',
            'account_number' => '+237699123123',
            'account_name' => 'Pub Admin',
        ]);

        $payout = Payout::query()->create([
            'reference' => (string) \Illuminate\Support\Str::uuid(),
            'publisher_id' => $pub->id,
            'payment_method_id' => $method->id,
            'amount_xaf' => 5000,
            'status' => PayoutStatus::Pending,
        ]);

        $this->actingAs($admin);

        Livewire::test(ValidationQueue::class)
            ->call('approveSite', $site->id)
            ->call('approveCampaign', $campaign->id);

        $this->assertSame(SiteStatus::Approved, $site->fresh()->status);
        $this->assertSame(CampaignStatus::Active, $campaign->fresh()->status);

        // Rejet d'un retrait -> remboursement dans le wallet
        Livewire::test(PayoutManager::class)
            ->call('reject', $payout->id);

        $this->assertSame(PayoutStatus::Rejected, $payout->fresh()->status);
        $this->assertSame(5000, $pubWallet->fresh()->available_balance_xaf);

        // Mise à jour des paramètres
        Livewire::test(PlatformSettings::class)
            ->set('publisherSharePercent', 75)
            ->set('dailyCampaignCostXaf', 2500)
            ->set('cpcUnitPriceXaf', 60)
            ->set('viewUnitPriceXaf', 10)
            ->call('save');

        $this->assertSame(75, (int) Setting::getValue('publisher_share_percent'));
        $this->assertSame(2500, (int) Setting::getValue('daily_campaign_cost_xaf'));
        $this->assertSame(60, (int) Setting::getValue('cpc_unit_price_xaf'));
        $this->assertSame(10, (int) Setting::getValue('view_unit_price_xaf'));
    }

    public function test_payout_deducts_balance_and_admin_sends_screenshot_proof_and_receipt(): void
    {
        Storage::fake('public');
        Notification::fake();

        $admin = User::query()->create([
            'name' => 'Admin Pay',
            'email' => 'adminpay@cm-ads.cm',
            'phone' => '+237690000999',
            'role' => UserRole::Admin,
            'password' => 'Admin@1234',
        ]);

        $pub = User::query()->create([
            'name' => 'Editeur 7500',
            'email' => 'pub7500@cm.cm',
            'phone' => '+237699007500',
            'role' => UserRole::Publisher,
            'password' => 'Secret@1234',
        ]);
        $wallet = Wallet::query()->create(['user_id' => $pub->id, 'available_balance_xaf' => 7500]);

        $method = PaymentMethod::query()->create([
            'user_id' => $pub->id,
            'provider' => 'mtn_momo',
            'account_number' => '+237677007500',
            'account_name' => 'Editeur 7500',
        ]);

        // Demande de 5 000 sur 7 500 -> il reste exactement 2 500 FCFA
        $this->actingAs($pub)->post('/publisher/payouts', [
            'payment_method_id' => $method->id,
            'amount_xaf' => 5000,
        ])->assertRedirect();

        $this->assertSame(2500, $wallet->fresh()->available_balance_xaf);
        $payout = Payout::query()->where('publisher_id', $pub->id)->firstOrFail();

        $screenshot = UploadedFile::fake()->image('momo-proof.png', 400, 600)->size(80);

        $this->actingAs($admin);
        Livewire::test(PayoutManager::class)
            ->call('openApproveModal', $payout->id)
            ->set('gatewayReference', 'MP261123.1620.XYZ')
            ->set('proofImage', $screenshot)
            ->call('approve');

        $payout->refresh();
        $this->assertSame(PayoutStatus::Paid, $payout->status);
        $this->assertSame('MP261123.1620.XYZ', $payout->gateway_reference);
        $this->assertNotNull($payout->proof_path);
        Storage::disk('public')->assertExists($payout->proof_path);
        $this->assertSame(2500, $wallet->fresh()->available_balance_xaf);

        Notification::assertSentTo($pub, PayoutApprovedNotification::class);

        $this->actingAs($pub)
            ->get("/publisher/payouts/{$payout->id}/receipt")
            ->assertOk()
            ->assertSee('MP261123.1620.XYZ');
    }

    public function test_admin_can_configure_branding_seo_and_maintenance_mode_blocks_public_only(): void
    {
        Storage::fake('public');

        $admin = User::query()->create([
            'name' => 'Admin Maint',
            'email' => 'maint@cm-ads.cm',
            'phone' => '+237690000888',
            'role' => UserRole::Admin,
            'password' => 'Admin@1234',
        ]);

        $logo = UploadedFile::fake()->image('site-logo.png', 180, 60)->size(30);
        $seo = UploadedFile::fake()->image('seo.jpg', 1200, 630)->size(100);

        $this->actingAs($admin);
        Livewire::test(PlatformSettings::class)
            ->set('siteName', 'Djocka Ads')
            ->set('siteDescription', 'Réseau publicitaire Cameroun')
            ->set('siteKeywords', 'ads, cameroun')
            ->set('siteEmail', 'hello@djocka.cm')
            ->set('sitePhone', '+237699000001')
            ->set('sitePostalBox', 'BP 999 Douala')
            ->set('siteLogo', $logo)
            ->set('seoImage', $seo)
            ->set('maintenanceMode', true)
            ->set('maintenanceMessage', 'Maintenance planifiée en cours.')
            ->call('save');

        $this->assertSame('Djocka Ads', Setting::getValue('site_name'));
        $this->assertNotEmpty(Setting::getValue('site_logo_path'));
        $this->assertNotEmpty(Setting::getValue('seo_image_path'));

        // Le site public est bloqué en 503 avec le message personnalisé
        $this->get('/')->assertStatus(503)->assertSee('Maintenance planifiée en cours.');

        // L'admin reste 100% accessible
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
    }
}
