<?php

namespace Tests\Feature;

use App\Enums\AdZoneFormat;
use App\Enums\BillingModel;
use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\SiteStatus;
use App\Enums\UserRole;
use App\Models\AdSlotMetric;
use App\Models\Campaign;
use App\Models\Site;
use App\Models\User;
use App\Services\AdSelection\SlotBanditService;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlotBanditRealtimeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
    }

    public function test_slot_bandit_learns_impressions_viewability_and_clicks_in_realtime(): void
    {
        $adv = User::query()->create([
            'name' => 'Adv Bandit',
            'email' => 'advbandit@cm.cm',
            'phone' => '+237677111999',
            'role' => UserRole::Advertiser,
            'password' => 'Secret1234',
        ]);
        $adv->wallet()->create(['available_balance_xaf' => 50000]);

        $pub = User::query()->create([
            'name' => 'Pub Bandit',
            'email' => 'pubbandit@cm.cm',
            'phone' => '+237699222999',
            'role' => UserRole::Publisher,
            'password' => 'Secret1234',
        ]);
        $pub->wallet()->create(['available_balance_xaf' => 0]);

        $site = Site::query()->create([
            'publisher_id' => $pub->id,
            'name' => 'Site IA',
            'domain' => 'siteia.cm',
            'category' => 'tech',
            'language' => 'fr',
            'status' => SiteStatus::Approved,
            'site_token' => 'bandittoken123456',
            'auto_ads_enabled' => true,
            'allowed_formats' => [AdZoneFormat::Native->value],
        ]);

        $campaign = Campaign::query()->create([
            'advertiser_id' => $adv->id,
            'name' => 'Campagne Bandit',
            'type' => CampaignType::Web,
            'billing_model' => BillingModel::Cpc,
            'status' => CampaignStatus::Active,
            'total_budget_xaf' => 20000,
            'daily_budget_xaf' => 2000,
            'unit_price_xaf' => 50,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(5),
        ]);

        $campaign->creatives()->create([
            'title' => 'Pub IA',
            'destination_url' => 'https://client.cm',
            'media_type' => 'image',
            'media_path' => 'creatives/demo.png',
            'format' => AdZoneFormat::Native->value,
            'file_size_bytes' => 1024,
        ]);

        // 1. Chargement du script <head> -> enregistre impressions_count en temps réel
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 13; Mobile)',
        ])->get("/a/{$site->site_token}.js");

        $response->assertOk();
        $content = str_replace(['\/', '\"'], ['/', '"'], $response->getContent());
        $this->assertStringContainsString('data-cm-slot', $content);

        // 2. Viewability via IntersectionObserver -> POST /m/v
        preg_match('/"view_token":"([^"]+)"/', $content, $viewMatch);
        $this->assertNotEmpty($viewMatch[1] ?? null);

        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 13; Mobile)',
        ])->post('/m/v', ['view_token' => $viewMatch[1]])->assertNoContent();

        // 3. Clic visiteur -> GET /click/{token}
        preg_match('/https?:\/\/[^\s"\'<>]+?\/click\/([A-Za-z0-9_\-=.%]+)/', $content, $clickMatch);
        $this->assertNotEmpty($clickMatch[1] ?? null);

        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 13; Mobile)',
        ])->get('/click/'.urldecode($clickMatch[1]))->assertRedirect('https://client.cm');

        $metric = AdSlotMetric::query()->where('site_id', $site->id)->where('clicks_count', '>=', 1)->first();
        $this->assertNotNull($metric);
        $this->assertGreaterThanOrEqual(1, $metric->impressions_count);
        $this->assertGreaterThanOrEqual(1, $metric->viewable_count);
        $this->assertGreaterThanOrEqual(35, $metric->earnings_xaf);

        // 4. Thompson Sampling favorise un slot très performant
        AdSlotMetric::query()->updateOrCreate(
            ['site_id' => $site->id, 'slot_key' => 'after_p2', 'device_type' => 'desktop'],
            ['impressions_count' => 100, 'viewable_count' => 90, 'clicks_count' => 30, 'earnings_xaf' => 1050]
        );
        AdSlotMetric::query()->updateOrCreate(
            ['site_id' => $site->id, 'slot_key' => 'before_footer', 'device_type' => 'desktop'],
            ['impressions_count' => 100, 'viewable_count' => 10, 'clicks_count' => 0, 'earnings_xaf' => 0]
        );

        $ranked = app(SlotBanditService::class)->rankSlots($site->id, 'desktop', 5);
        $this->assertLessThan(
            array_search('before_footer', $ranked, true),
            array_search('after_p2', $ranked, true)
        );

        // 5. Visible dans /publisher/stats
        $this->actingAs($pub)->get('/publisher/stats')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Publisher/Stats/Index')
                ->has('slotPerformance')
            );
    }

    public function test_unique_scroll_view_and_click_combine_earnings_with_3_xaf_default(): void
    {
        $this->assertSame(3, (int) \App\Models\Setting::getValue('view_unit_price_xaf'));
        \App\Models\Setting::setValue('publisher_share_percent', 100);

        $adv = User::query()->create([
            'name' => 'Adv Combo',
            'email' => 'advcombo@cm.cm',
            'phone' => '+237677333111',
            'role' => UserRole::Advertiser,
            'password' => 'Secret1234',
        ]);
        $adv->wallet()->create(['available_balance_xaf' => 50000]);

        $pub = User::query()->create([
            'name' => 'Pub Combo',
            'email' => 'pubcombo@cm.cm',
            'phone' => '+237699333111',
            'role' => UserRole::Publisher,
            'password' => 'Secret1234',
        ]);
        $pubWallet = $pub->wallet()->create(['available_balance_xaf' => 0]);

        $site = Site::query()->create([
            'publisher_id' => $pub->id,
            'name' => 'Site Combo',
            'domain' => 'combo.cm',
            'category' => 'tech',
            'language' => 'fr',
            'status' => SiteStatus::Approved,
            'site_token' => 'combotoken1234567',
            'auto_ads_enabled' => true,
            'allowed_formats' => [AdZoneFormat::Native->value],
        ]);

        $campaign = Campaign::query()->create([
            'advertiser_id' => $adv->id,
            'name' => 'Campagne Combo 53F',
            'type' => CampaignType::Web,
            'billing_model' => BillingModel::Cpc,
            'status' => CampaignStatus::Active,
            'total_budget_xaf' => 20000,
            'daily_budget_xaf' => 2000,
            'unit_price_xaf' => 50,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(5),
        ]);

        $campaign->creatives()->create([
            'title' => 'Pub Combo',
            'destination_url' => 'https://client.cm',
            'media_type' => 'image',
            'media_path' => 'creatives/demo.png',
            'format' => AdZoneFormat::Native->value,
            'file_size_bytes' => 1024,
        ]);

        $res = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 13; Mobile)',
        ])->get("/a/{$site->site_token}.js")->assertOk();

        $content = str_replace(['\/', '\"'], ['/', '"'], $res->getContent());
        preg_match('/"view_token":"([^"]+)"/', $content, $viewMatch);
        preg_match('/https?:\/\/[^\s"\'<>]+?\/click\/([A-Za-z0-9_\-=.%]+)/', $content, $clickMatch);

        // 1re vue au scroll (via pixel GET /m/v cross-origin) -> +3 FCFA
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Linux; Android 13; Mobile)'])
            ->get('/m/v?view_token='.urlencode($viewMatch[1]))
            ->assertOk();
        $this->assertSame(3, $pubWallet->fresh()->pending_balance_xaf);

        // 2e visite / retour sur la même page -> vue unique déjà comptée, 0 FCFA supplémentaire
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Linux; Android 13; Mobile)'])
            ->post('/m/v', ['view_token' => $viewMatch[1]])
            ->assertNoContent();
        $this->assertSame(3, $pubWallet->fresh()->pending_balance_xaf);

        // Clic sur l'annonce -> +50 FCFA => total 53 FCFA
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Linux; Android 13; Mobile)'])
            ->get('/click/'.urldecode($clickMatch[1]))
            ->assertRedirect('https://client.cm');
        $this->assertSame(53, $pubWallet->fresh()->pending_balance_xaf);
    }
}
