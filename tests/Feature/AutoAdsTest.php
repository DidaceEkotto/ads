<?php

namespace Tests\Feature;

use App\Enums\AdZoneFormat;
use App\Enums\BillingModel;
use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\SiteStatus;
use App\Enums\UserRole;
use App\Models\Campaign;
use App\Models\Site;
use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoAdsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
    }

    public function test_publisher_gets_single_head_script_and_selects_multiple_ad_formats(): void
    {
        $publisher = User::query()->create([
            'name' => 'Editeur Auto',
            'email' => 'auto@cm.cm',
            'phone' => '+237699333111',
            'role' => UserRole::Publisher,
            'password' => 'Secret@1234',
        ]);

        $this->actingAs($publisher)->post('/publisher/sites', [
            'name' => 'Blog Auto',
            'domain' => 'blogauto.cm',
            'category' => 'tech',
            'language' => 'fr',
            'allowed_formats' => [AdZoneFormat::Banner728x90->value, AdZoneFormat::Native->value],
        ])->assertRedirect();

        $site = Site::query()->firstOrFail();
        $this->assertNotNull($site->site_token);
        $this->assertTrue($site->auto_ads_enabled);
        $this->assertSame(['banner_728x90', 'native'], $site->allowed_formats);
        $this->assertStringContainsString("/a/{$site->site_token}.js", $site->autoAdsHeadSnippet());

        // Mise à jour des formats par l'éditeur
        $this->actingAs($publisher)->patch("/publisher/sites/{$site->id}/auto-ads", [
            'auto_ads_enabled' => true,
            'allowed_formats' => [AdZoneFormat::Square300x250->value, AdZoneFormat::Native->value],
        ])->assertRedirect();

        $this->assertSame(['square_300x250', 'native'], $site->fresh()->allowed_formats);
    }

    public function test_auto_ads_head_script_places_ads_and_records_impressions_when_site_approved(): void
    {
        $adv = User::query()->create([
            'name' => 'Adv Auto',
            'email' => 'advauto@cm.cm',
            'phone' => '+237677333111',
            'role' => UserRole::Advertiser,
            'password' => 'Secret@1234',
        ]);
        Wallet::query()->create(['user_id' => $adv->id, 'available_balance_xaf' => 5000]);

        $pub = User::query()->create([
            'name' => 'Pub Auto',
            'email' => 'pubauto@cm.cm',
            'phone' => '+237699333222',
            'role' => UserRole::Publisher,
            'password' => 'Secret@1234',
        ]);

        $campaign = Campaign::query()->create([
            'advertiser_id' => $adv->id,
            'name' => 'Campagne Native Auto',
            'type' => CampaignType::Web,
            'billing_model' => BillingModel::Cpc,
            'status' => CampaignStatus::Active,
            'total_budget_xaf' => 5000,
            'unit_price_xaf' => 50,
        ]);
        $campaign->creatives()->create([
            'title' => 'Offre Auto',
            'body_text' => 'Texte natif',
            'destination_url' => 'https://client.cm',
            'media_type' => 'image',
            'media_path' => 'creatives/demo/promo.png',
            'format' => AdZoneFormat::Native->value,
            'file_size_bytes' => 10000,
            'is_active' => true,
        ]);

        $site = Site::query()->create([
            'publisher_id' => $pub->id,
            'name' => 'News Auto',
            'domain' => 'newsauto.cm',
            'category' => 'tech',
            'language' => 'fr',
            'status' => SiteStatus::Approved,
            'site_token' => 'autoadtoken12345678',
            'auto_ads_enabled' => true,
            'allowed_formats' => [AdZoneFormat::Native->value],
        ]);

        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 13; Mobile)',
        ])->get("/a/{$site->site_token}.js");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/javascript; charset=UTF-8');
        $this->assertStringContainsString('cm-auto-slot', $response->getContent());
        $this->assertStringContainsString('IntersectionObserver', $response->getContent());
        $this->assertDatabaseCount('impressions', 1);
    }
}
