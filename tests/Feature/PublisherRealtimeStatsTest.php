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
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublisherRealtimeStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
    }

    public function test_publisher_stats_page_computes_realtime_metrics_without_cron_or_jobs(): void
    {
        $adv = User::query()->create([
            'name' => 'Annonceur RT',
            'email' => 'advrt@cm.cm',
            'phone' => '+237677111000',
            'role' => UserRole::Advertiser,
            'password' => 'Secret1234',
        ]);

        $pub = User::query()->create([
            'name' => 'Editeur RT',
            'email' => 'pubrt@cm.cm',
            'phone' => '+237699222000',
            'role' => UserRole::Publisher,
            'password' => 'Secret1234',
        ]);

        $site = Site::query()->create([
            'publisher_id' => $pub->id,
            'name' => 'Blog Cameroun',
            'domain' => 'blogcameroun.cm',
            'category' => 'actualites',
            'language' => 'fr',
            'status' => SiteStatus::Approved,
        ]);

        $campaign = Campaign::query()->create([
            'advertiser_id' => $adv->id,
            'name' => 'Campagne Temps Réel',
            'type' => CampaignType::Web,
            'billing_model' => BillingModel::Cpc,
            'status' => CampaignStatus::Active,
            'total_budget_xaf' => 20000,
            'daily_budget_xaf' => 2000,
            'unit_price_xaf' => 50,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(5),
        ]);

        $creative = $campaign->creatives()->create([
            'title' => 'Annonce RT',
            'destination_url' => 'https://client.cm',
            'media_type' => 'image',
            'media_path' => 'creatives/demo.png',
            'format' => 'square_300x250',
            'file_size_bytes' => 1024,
        ]);

        Impression::query()->create([
            'campaign_id' => $campaign->id,
            'creative_id' => $creative->id,
            'site_id' => $site->id,
            'publisher_id' => $pub->id,
            'visitor_hash' => 'vis-1',
            'ip_address' => '197.242.1.1',
            'country' => 'Cameroun',
            'city' => 'Douala',
            'device_type' => 'mobile',
            'cost_xaf' => 0,
            'created_at' => now(),
        ]);

        Click::query()->create([
            'campaign_id' => $campaign->id,
            'site_id' => $site->id,
            'publisher_id' => $pub->id,
            'visitor_hash' => 'vis-1',
            'ip_address' => '197.242.1.1',
            'country' => 'Cameroun',
            'city' => 'Douala',
            'device_type' => 'mobile',
            'is_valid' => true,
            'fraud_score' => 0,
            'cost_xaf' => 50,
            'publisher_earning_xaf' => 35,
            'platform_fee_xaf' => 15,
            'earning_status' => 'pending',
            'created_at' => now(),
        ]);

        // Aucun StatsDaily généré : la page calcule tout en direct
        $this->assertDatabaseCount('stats_daily', 0);

        $this->actingAs($pub)->get('/publisher/stats')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Publisher/Stats/Index')
                ->where('summary.total_impressions', 1)
                ->where('summary.valid_clicks', 1)
                ->where('summary.total_earnings_xaf', 35)
                ->where('summary.pending_earnings_xaf', 35)
                ->where('byCampaign.0.name', 'Campagne Temps Réel')
                ->where('byCampaign.0.earnings_xaf', 35)
                ->where('bySite.0.name', 'Blog Cameroun')
            );
    }
}
