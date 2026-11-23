<?php

namespace Tests\Feature;

use App\Enums\BillingModel;
use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\SocialPlacementStatus;
use App\Enums\SocialPlatform;
use App\Enums\UserRole;
use App\Models\Campaign;
use App\Models\Setting;
use App\Models\SocialAccount;
use App\Models\SocialPlacement;
use App\Models\TrackingLink;
use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialCampaignsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
        Setting::setValue('cpc_unit_price_xaf', 100, 'integer');
    }

    public function test_social_account_can_be_added_without_followers_and_generates_bio_code(): void
    {
        $publisher = User::query()->create([
            'name' => 'Influenceur CM',
            'email' => 'inf@cm.cm',
            'phone' => '+237699000111',
            'role' => UserRole::Publisher,
            'password' => 'Secret@1234',
        ]);

        $this->actingAs($publisher)->post('/publisher/social/accounts', [
            'platform' => SocialPlatform::Facebook->value,
            'handle' => '@CamerounActu',
            'profile_url' => 'https://facebook.com/CamerounActu',
        ])->assertRedirect();

        $account = SocialAccount::query()->firstOrFail();
        $this->assertSame('camerounactu', strtolower($account->handle));
        $this->assertSame(0, $account->followers_count);
        $this->assertStringStartsWith('CMADS-', $account->verification_code);
    }

    public function test_social_tracking_link_filters_preview_bots_and_bills_in_app_mobile_clicks(): void
    {
        $advertiser = User::query()->create([
            'name' => 'Adv Social',
            'email' => 'advsoc@cm.cm',
            'phone' => '+237677000888',
            'role' => UserRole::Advertiser,
            'password' => 'Secret1234',
        ]);
        Wallet::query()->create(['user_id' => $advertiser->id, 'available_balance_xaf' => 5000]);

        $publisher = User::query()->create([
            'name' => 'Pub Social',
            'email' => 'pubsoc@cm.cm',
            'phone' => '+237699000888',
            'role' => UserRole::Publisher,
            'password' => 'Secret1234',
        ]);
        Wallet::query()->create(['user_id' => $publisher->id]);

        $campaign = Campaign::query()->create([
            'advertiser_id' => $advertiser->id,
            'name' => 'Campagne Facebook',
            'type' => CampaignType::Social,
            'billing_model' => BillingModel::Cpc,
            'status' => CampaignStatus::Active,
            'total_budget_xaf' => 5000,
            'unit_price_xaf' => 100,
        ]);
        $campaign->creatives()->create([
            'title' => 'Nouveau service Mobile',
            'body_text' => 'Inscrivez-vous en 2 min #pub',
            'destination_url' => 'https://service.cm',
            'media_type' => 'image',
            'media_path' => 'creatives/2026/11/soc.png',
            'format' => 'social',
            'file_size_bytes' => 15000,
        ]);

        $account = SocialAccount::query()->create([
            'publisher_id' => $publisher->id,
            'platform' => SocialPlatform::Facebook,
            'handle' => 'pagecm',
            'profile_url' => 'https://facebook.com/pagecm',
            'followers_count' => 8000,
            'verification_code' => 'CMADS-123456',
            'status' => 'approved',
        ]);

        $this->actingAs($publisher)->post("/publisher/social/campaigns/{$campaign->id}/join", [
            'social_account_id' => $account->id,
        ])->assertRedirect();

        $placement = SocialPlacement::query()->firstOrFail();
        $link = TrackingLink::query()->firstOrFail();

        // Soumission de la preuve
        $this->actingAs($publisher)->post("/publisher/social/placements/{$placement->id}/proof", [
            'post_url' => 'https://facebook.com/pagecm/posts/999',
            'has_disclosure_tag' => true,
        ])->assertRedirect();
        $this->assertSame(SocialPlacementStatus::PendingReview, $placement->fresh()->status);

        // 1. Robot d'aperçu Facebook -> 200 OpenGraph, 0 clic facturé
        $this->withHeaders(['User-Agent' => 'facebookexternalhit/1.1'])
            ->get("/c/{$link->code}")
            ->assertOk()
            ->assertSee('og:title', false);

        $this->assertDatabaseCount('clicks', 0);
        $this->assertSame(5000, $advertiser->wallet->fresh()->available_balance_xaf);

        // 2. Navigateur intégré Facebook (FBAN/FBIOS) -> redirection + facturation 70/30
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) [FBAN/FBIOS;FBAV/430.0]'])
            ->get("/c/{$link->code}")
            ->assertRedirect('https://service.cm');

        $this->assertDatabaseCount('clicks', 1);
        $this->assertSame(4900, $advertiser->wallet->fresh()->available_balance_xaf);
        $this->assertSame(70, $publisher->wallet->fresh()->pending_balance_xaf);
    }
}
