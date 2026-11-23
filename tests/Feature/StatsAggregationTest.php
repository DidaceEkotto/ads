<?php

namespace Tests\Feature;

use App\Enums\BillingModel;
use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\SiteStatus;
use App\Enums\UserRole;
use App\Jobs\AggregateDailyStatsJob;
use App\Models\Campaign;
use App\Models\Click;
use App\Models\Impression;
use App\Models\Site;
use App\Models\StatsDaily;
use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StatsAggregationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
    }

    public function test_daily_aggregation_is_idempotent_and_feeds_dashboards(): void
    {
        $adv = User::query()->create([
            'name' => 'Adv Stats',
            'email' => 'advstats@cm.cm',
            'phone' => '+237677999000',
            'role' => UserRole::Advertiser,
            'password' => 'Secret1234',
        ]);
        Wallet::query()->create(['user_id' => $adv->id, 'available_balance_xaf' => 10000]);

        $pub = User::query()->create([
            'name' => 'Pub Stats',
            'email' => 'pubstats@cm.cm',
            'phone' => '+237699999000',
            'role' => UserRole::Publisher,
            'password' => 'Secret1234',
        ]);
        Wallet::query()->create(['user_id' => $pub->id]);

        $campaign = Campaign::query()->create([
            'advertiser_id' => $adv->id,
            'name' => 'Campagne Low Budget',
            'type' => CampaignType::Web,
            'billing_model' => BillingModel::Cpc,
            'status' => CampaignStatus::Active,
            'total_budget_xaf' => 1000,
            'spent_xaf' => 850, // < 20% restant -> alerte budget bas
            'unit_price_xaf' => 100,
        ]);

        $site = Site::query()->create([
            'publisher_id' => $pub->id,
            'name' => 'Site Stats',
            'domain' => 'stats.cm',
            'category' => 'tech',
            'language' => 'fr',
            'status' => SiteStatus::Approved,
        ]);

        $today = now()->toDateString();

        Impression::query()->create([
            'campaign_id' => $campaign->id,
            'creative_id' => 1,
            'site_id' => $site->id,
            'ad_zone_id' => 1,
            'publisher_id' => $pub->id,
            'visitor_hash' => str_repeat('a', 64),
            'ip_address' => '1.1.1.1',
            'device_type' => 'mobile',
            'created_at' => now(),
        ]);

        Click::query()->create([
            'campaign_id' => $campaign->id,
            'creative_id' => 1,
            'site_id' => $site->id,
            'ad_zone_id' => 1,
            'publisher_id' => $pub->id,
            'visitor_hash' => str_repeat('a', 64),
            'ip_address' => '1.1.1.1',
            'device_type' => 'mobile',
            'is_valid' => true,
            'cost_xaf' => 100,
            'publisher_earning_xaf' => 70,
            'platform_fee_xaf' => 30,
            'created_at' => now(),
        ]);

        Cache::put("rt_adblock:{$today}:s:{$site->id}", 1);

        // Exécuter 2 fois pour vérifier l'idempotence
        AggregateDailyStatsJob::dispatchSync($today);
        AggregateDailyStatsJob::dispatchSync($today);

        $this->assertSame(1, StatsDaily::query()->where('campaign_id', $campaign->id)->count());
        $row = StatsDaily::query()->where('campaign_id', $campaign->id)->firstOrFail();
        $this->assertSame(1, $row->impressions_count);
        $this->assertSame(1, $row->valid_clicks_count);
        $this->assertSame(100, $row->spend_xaf);
        $this->assertSame(70, $row->publisher_earning_xaf);

        $this->actingAs($adv)->get('/advertiser/dashboard')->assertOk();
        $this->actingAs($pub)->get('/publisher/dashboard')->assertOk();
    }
}
