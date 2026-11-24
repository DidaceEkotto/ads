<?php

namespace Tests\Feature;

use App\Enums\BillingModel;
use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\UserRole;
use App\Models\Campaign;
use App\Models\Setting;
use App\Models\User;
use App\Services\Automation\DynamicCronService;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicCronTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
    }

    public function test_dynamic_cron_expires_ended_campaigns_and_endpoint_works(): void
    {
        $adv = User::query()->create([
            'name' => 'Adv Cron',
            'email' => 'advcron@cm.cm',
            'phone' => '+237677111222',
            'role' => UserRole::Advertiser,
            'password' => 'Secret1234',
        ]);

        $expired = Campaign::query()->create([
            'advertiser_id' => $adv->id,
            'name' => 'Campagne Expirée',
            'type' => CampaignType::Web,
            'billing_model' => BillingModel::Cpc,
            'status' => CampaignStatus::Active,
            'total_budget_xaf' => 10000,
            'daily_budget_xaf' => 2000,
            'unit_price_xaf' => 50,
            'starts_at' => now()->subDays(6),
            'ends_at' => now()->subHour(),
        ]);

        $active = Campaign::query()->create([
            'advertiser_id' => $adv->id,
            'name' => 'Campagne En Cours',
            'type' => CampaignType::Web,
            'billing_model' => BillingModel::Cpc,
            'status' => CampaignStatus::Active,
            'total_budget_xaf' => 10000,
            'daily_budget_xaf' => 2000,
            'unit_price_xaf' => 50,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(4),
        ]);

        $result = app(DynamicCronService::class)->runDueTasks();

        $this->assertSame(1, $result['expired_campaigns']);
        $this->assertTrue($result['stats_aggregated']);
        $this->assertSame(CampaignStatus::Completed, $expired->fresh()->status);
        $this->assertSame(CampaignStatus::Active, $active->fresh()->status);

        Setting::setValue('cron_secret_token', 'secret-cron-123');

        $this->get('/cron/run?token=wrong')->assertForbidden();
        $this->get('/cron/run?token=secret-cron-123')
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }
}
