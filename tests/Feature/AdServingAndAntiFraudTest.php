<?php

namespace Tests\Feature;

use App\Enums\AdZoneFormat;
use App\Enums\BillingModel;
use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\FraudReason;
use App\Enums\SiteStatus;
use App\Enums\UserRole;
use App\Jobs\ProcessAdClickJob;
use App\Models\AdZone;
use App\Models\Campaign;
use App\Models\Click;
use App\Models\FraudFlag;
use App\Models\Setting;
use App\Models\Site;
use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdServingAndAntiFraudTest extends TestCase
{
    use RefreshDatabase;

    private User $advertiser;

    private User $publisher;

    private Campaign $campaign;

    private AdZone $zone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
        Setting::setValue('cpc_unit_price_xaf', 100, 'integer');

        $this->advertiser = User::query()->create([
            'name' => 'Annonceur Pub',
            'email' => 'advpub@cm.cm',
            'phone' => '+237677888999',
            'role' => UserRole::Advertiser,
            'password' => 'Secret1234',
        ]);
        Wallet::query()->create([
            'user_id' => $this->advertiser->id,
            'available_balance_xaf' => 5000,
        ]);

        $this->publisher = User::query()->create([
            'name' => 'Editeur Pub',
            'email' => 'pubad@cm.cm',
            'phone' => '+237699888999',
            'role' => UserRole::Publisher,
            'password' => 'Secret1234',
        ]);
        Wallet::query()->create(['user_id' => $this->publisher->id]);

        $this->campaign = Campaign::query()->create([
            'advertiser_id' => $this->advertiser->id,
            'name' => 'Campagne CPC Douala',
            'type' => CampaignType::Web,
            'billing_model' => BillingModel::Cpc,
            'status' => CampaignStatus::Active,
            'total_budget_xaf' => 5000,
            'unit_price_xaf' => 100,
        ]);

        $this->campaign->creatives()->create([
            'title' => 'Offre Spéciale',
            'body_text' => 'Découvrez maintenant',
            'destination_url' => 'https://client.cm/offre',
            'media_type' => 'image',
            'media_path' => 'creatives/2026/11/test.png',
            'format' => AdZoneFormat::Square300x250->value,
            'file_size_bytes' => 12000,
        ]);

        $site = Site::query()->create([
            'publisher_id' => $this->publisher->id,
            'name' => 'Mon Blog',
            'domain' => 'monblog.cm',
            'category' => 'actualites',
            'language' => 'fr',
            'status' => SiteStatus::Approved,
        ]);

        $this->zone = AdZone::query()->create([
            'site_id' => $site->id,
            'name' => 'Sidebar',
            'token' => 'zone1234abcd5678',
            'format' => AdZoneFormat::Square300x250,
            'is_native' => false,
            'is_active' => true,
        ]);
    }

    public function test_public_script_serves_ad_records_impression_and_valid_click_bills_70_30(): void
    {
        $jsResponse = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 13; Mobile) AppleWebKit/537.36',
        ])->get("/d/{$this->zone->token}.js");

        $jsResponse->assertOk();
        $jsResponse->assertHeader('Content-Type', 'application/javascript; charset=UTF-8');
        $content = $jsResponse->getContent();
        $this->assertStringContainsString('cm-rec-card', $content);
        $this->assertDatabaseCount('impressions', 1);

        // Extraire l'URL /click/{token} générée dans le JS
        $normalized = str_replace('\\/', '/', $content);
        preg_match('#/click/([^"\\\\]+)#', $normalized, $matches);
        $this->assertNotEmpty($matches[1]);

        $clickResponse = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 13; Mobile) AppleWebKit/537.36',
        ])->get('/click/'.$matches[1]);

        $clickResponse->assertRedirect('https://client.cm/offre');

        $this->assertDatabaseCount('clicks', 1);
        $click = Click::query()->firstOrFail();
        $this->assertTrue($click->is_valid);
        $this->assertSame(100, $click->cost_xaf);
        $this->assertSame(70, $click->publisher_earning_xaf);
        $this->assertSame(30, $click->platform_fee_xaf);
        $this->assertSame('pending', $click->earning_status);

        $this->assertSame(4900, $this->advertiser->wallet->fresh()->available_balance_xaf);
        $this->assertSame(70, $this->publisher->wallet->fresh()->pending_balance_xaf);
        $this->assertSame(100, $this->campaign->fresh()->spent_xaf);
    }

    public function test_duplicate_click_bot_and_self_click_are_flagged_and_not_billed(): void
    {
        // 1. Bot User-Agent
        ProcessAdClickJob::dispatchSync([
            'campaign_id' => $this->campaign->id,
            'creative_id' => $this->campaign->creatives->first()->id,
            'site_id' => $this->zone->site_id,
            'ad_zone_id' => $this->zone->id,
            'social_placement_id' => null,
            'tracking_link_id' => null,
            'publisher_id' => $this->publisher->id,
            'visitor_hash' => hash('sha256', '1.1.1.1|curl'),
            'ip_address' => '1.1.1.1',
            'user_agent' => 'curl/8.0.1',
            'referer' => null,
            'city' => null,
            'device_type' => 'desktop',
            'authenticated_user_id' => null,
        ]);

        // 2. Auto-clic éditeur
        ProcessAdClickJob::dispatchSync([
            'campaign_id' => $this->campaign->id,
            'creative_id' => $this->campaign->creatives->first()->id,
            'site_id' => $this->zone->site_id,
            'ad_zone_id' => $this->zone->id,
            'social_placement_id' => null,
            'tracking_link_id' => null,
            'publisher_id' => $this->publisher->id,
            'visitor_hash' => hash('sha256', '2.2.2.2|mozilla'),
            'ip_address' => '2.2.2.2',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0)',
            'referer' => null,
            'city' => null,
            'device_type' => 'desktop',
            'authenticated_user_id' => $this->publisher->id,
        ]);

        // 3. Clic valide puis clic dupliqué du même visiteur
        $humanHash = hash('sha256', '3.3.3.3|mozilla');
        $humanPayload = [
            'campaign_id' => $this->campaign->id,
            'creative_id' => $this->campaign->creatives->first()->id,
            'site_id' => $this->zone->site_id,
            'ad_zone_id' => $this->zone->id,
            'social_placement_id' => null,
            'tracking_link_id' => null,
            'publisher_id' => $this->publisher->id,
            'visitor_hash' => $humanHash,
            'ip_address' => '3.3.3.3',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'referer' => null,
            'city' => null,
            'device_type' => 'desktop',
            'authenticated_user_id' => null,
        ];
        ProcessAdClickJob::dispatchSync($humanPayload);
        ProcessAdClickJob::dispatchSync($humanPayload);

        // Seul 1 clic sur 4 a été facturé
        $this->assertSame(4900, $this->advertiser->wallet->fresh()->available_balance_xaf);
        $this->assertSame(70, $this->publisher->wallet->fresh()->pending_balance_xaf);
        $this->assertSame(3, FraudFlag::query()->count());
        $this->assertTrue(FraudFlag::query()->where('reason', FraudReason::SelfClick)->exists());
        $this->assertTrue(FraudFlag::query()->where('reason', FraudReason::BotUserAgent)->exists());
        $this->assertTrue(FraudFlag::query()->where('reason', FraudReason::DuplicateClick)->exists());
    }

    public function test_adblock_metric_endpoint_records_hit(): void
    {
        $this->post('/m/ab', ['zone_token' => $this->zone->token])->assertNoContent();

        $dateKey = now()->format('Y-m-d');
        $this->assertSame(1, (int) Cache::get("rt_adblock:{$dateKey}:s:{$this->zone->site_id}"));
    }
}
