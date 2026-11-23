<?php

namespace Tests\Feature;

use App\Enums\BillingModel;
use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\SiteStatus;
use App\Enums\UserRole;
use App\Models\Campaign;
use App\Models\Click;
use App\Models\Impression;
use App\Models\Site;
use App\Models\User;
use App\Services\Geo\GeoLocationService;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CampaignAnalyticsAndGeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
    }

    public function test_geolocation_service_resolves_public_ip_with_cache_and_handles_private_ip(): void
    {
        Http::fake([
            'http://ip-api.com/json/41.202.219.1*' => Http::response([
                'status' => 'success',
                'country' => 'Cameroun',
                'city' => 'Yaoundé',
            ], 200),
        ]);

        $geo = app(GeoLocationService::class);

        // IP privée -> valeur par défaut locale sans appel HTTP
        $local = $geo->locate('127.0.0.1');
        $this->assertSame('Cameroun', $local['country']);
        $this->assertSame('Douala', $local['city']);

        // IP publique -> appel API + mise en cache
        $public = $geo->locate('41.202.219.1');
        $this->assertSame('Cameroun', $public['country']);
        $this->assertSame('Yaoundé', $public['city']);

        // 2e appel identique -> lu depuis le cache (1 seule requête HTTP envoyée)
        $geo->locate('41.202.219.1');
        Http::assertSentCount(1);
    }

    public function test_advertiser_can_view_campaign_analytics_with_sites_unique_clients_geo_and_bots(): void
    {
        $adv = User::query()->create([
            'name' => 'Adv Analytics',
            'email' => 'adva@cm.cm',
            'phone' => '+237677111222',
            'role' => UserRole::Advertiser,
            'password' => 'Secret@1234',
        ]);

        $pub = User::query()->create([
            'name' => 'Pub Analytics',
            'email' => 'puba@cm.cm',
            'phone' => '+237699111222',
            'role' => UserRole::Publisher,
            'password' => 'Secret@1234',
        ]);

        $site = Site::query()->create([
            'publisher_id' => $pub->id,
            'name' => 'Cameroon Tribune Démo',
            'domain' => 'cameroon-demo.cm',
            'category' => 'actualites',
            'language' => 'fr',
            'status' => SiteStatus::Approved,
        ]);

        $campaign = Campaign::query()->create([
            'advertiser_id' => $adv->id,
            'name' => 'Campagne Geo',
            'type' => CampaignType::Web,
            'billing_model' => BillingModel::Cpc,
            'status' => CampaignStatus::Active,
            'total_budget_xaf' => 10000,
            'unit_price_xaf' => 50,
        ]);

        // 2 vues du même visiteur (1 client unique) + 1 vue d'un autre visiteur = 3 vues, 2 clients uniques
        $hash1 = hash('sha256', 'v1');
        $hash2 = hash('sha256', 'v2');

        Impression::query()->create([
            'campaign_id' => $campaign->id, 'creative_id' => 1, 'site_id' => $site->id, 'ad_zone_id' => 1,
            'publisher_id' => $pub->id, 'visitor_hash' => $hash1, 'ip_address' => '41.202.219.1',
            'country' => 'Cameroun', 'city' => 'Douala', 'device_type' => 'mobile', 'created_at' => now(),
        ]);
        Impression::query()->create([
            'campaign_id' => $campaign->id, 'creative_id' => 1, 'site_id' => $site->id, 'ad_zone_id' => 1,
            'publisher_id' => $pub->id, 'visitor_hash' => $hash1, 'ip_address' => '41.202.219.1',
            'country' => 'Cameroun', 'city' => 'Douala', 'device_type' => 'mobile', 'created_at' => now(),
        ]);
        Impression::query()->create([
            'campaign_id' => $campaign->id, 'creative_id' => 1, 'site_id' => $site->id, 'ad_zone_id' => 1,
            'publisher_id' => $pub->id, 'visitor_hash' => $hash2, 'ip_address' => '41.202.219.2',
            'country' => 'Cameroun', 'city' => 'Yaoundé', 'device_type' => 'desktop', 'created_at' => now(),
        ]);

        // 1 clic valide + 1 bot bloqué
        Click::query()->create([
            'campaign_id' => $campaign->id, 'site_id' => $site->id, 'publisher_id' => $pub->id,
            'visitor_hash' => $hash1, 'ip_address' => '41.202.219.1', 'country' => 'Cameroun', 'city' => 'Douala',
            'device_type' => 'mobile', 'is_valid' => true, 'created_at' => now(),
        ]);
        Click::query()->create([
            'campaign_id' => $campaign->id, 'site_id' => $site->id, 'publisher_id' => $pub->id,
            'visitor_hash' => hash('sha256', 'bot'), 'ip_address' => '1.2.3.4', 'country' => 'France', 'city' => 'Paris',
            'device_type' => 'desktop', 'is_valid' => false, 'invalid_reason' => 'bot_ua', 'created_at' => now(),
        ]);

        $this->actingAs($adv)
            ->get("/advertiser/campaigns/{$campaign->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Advertiser/Campaigns/Show')
                ->where('summary.sites_count', 1)
                ->where('summary.views_count', 3)
                ->where('summary.unique_visitors_count', 2)
                ->where('summary.valid_clicks_count', 1)
                ->where('summary.blocked_bots_count', 1)
            );
    }
}
