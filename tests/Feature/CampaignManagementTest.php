<?php

namespace Tests\Feature;

use App\Enums\BillingModel;
use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\UserRole;
use App\Models\Campaign;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CampaignManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
        Storage::fake('public');
    }

    public function test_advertiser_can_create_campaign_with_valid_lightweight_image_and_targeting(): void
    {
        $advertiser = User::query()->create([
            'name' => 'Annonceur Campagne',
            'email' => 'camp@cm.cm',
            'phone' => '+237677333444',
            'role' => UserRole::Advertiser,
            'password' => 'Secret1234',
        ]);
        $advertiser->wallet()->updateOrCreate(
            ['user_id' => $advertiser->id],
            ['available_balance_xaf' => 15000]
        );

        // Image PNG 10 Ko (< 150 Ko)
        $image = UploadedFile::fake()->image('promo.png', 300, 250)->size(10);

        $this->actingAs($advertiser)->get('/advertiser/campaigns/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Advertiser/Campaigns/Create')
                ->where('walletBalanceXaf', 15000)
            );

        $response = $this->actingAs($advertiser)->post('/advertiser/campaigns', [
            'name' => 'Promo Rentrée Douala',
            'type' => CampaignType::Web->value,
            'billing_model' => BillingModel::Cpc->value,
            'duration_days' => 5,
            'title' => 'Réduction -30% à Douala',
            'body_text' => 'Profitez de nos offres.',
            'destination_url' => 'https://boutique.cm/promo',
            'format' => 'square_300x250',
            'media' => $image,
            'cities' => ['Douala', 'Yaoundé'],
            'languages' => ['fr'],
            'devices' => ['mobile'],
            'categories' => ['business'],
        ]);

        $response->assertRedirect(route('advertiser.campaigns.index'));

        $campaign = Campaign::query()->with(['creatives', 'targeting'])->firstOrFail();
        $this->assertSame(CampaignStatus::PendingReview, $campaign->status);
        $this->assertSame(10000, $campaign->total_budget_xaf); // 5 jours x 2000 FCFA
        $this->assertSame(2000, $campaign->daily_budget_xaf);
        $this->assertSame(50, $campaign->unit_price_xaf);
        $this->assertCount(1, $campaign->creatives);
        $this->assertSame(['Douala', 'Yaoundé'], $campaign->targeting->cities);
        Storage::disk('public')->assertExists($campaign->creatives->first()->media_path);
    }

    public function test_oversized_image_over_150kb_and_fake_image_are_rejected(): void
    {
        $advertiser = User::query()->create([
            'name' => 'Annonceur Limite',
            'email' => 'limit@cm.cm',
            'phone' => '+237677555666',
            'role' => UserRole::Advertiser,
            'password' => 'Secret1234',
        ]);

        // Image PNG de 200 Ko (> 150 Ko)
        $heavyImage = UploadedFile::fake()->image('heavy.png', 300, 250)->size(200);

        $this->actingAs($advertiser)->post('/advertiser/campaigns', [
            'name' => 'Trop lourd',
            'type' => CampaignType::Web->value,
            'billing_model' => BillingModel::Cpc->value,
            'duration_days' => 3,
            'title' => 'Titre',
            'destination_url' => 'https://boutique.cm',
            'format' => 'square_300x250',
            'media' => $heavyImage,
            'cities' => ['Douala'],
        ])->assertSessionHasErrors('media');

        // Faux fichier PHP renommé en .jpg
        $fakePhp = UploadedFile::fake()->createWithContent('shell.jpg', '<?php system($_GET["c"]); ?>');

        $this->actingAs($advertiser)->post('/advertiser/campaigns', [
            'name' => 'Faux fichier',
            'type' => CampaignType::Web->value,
            'billing_model' => BillingModel::Cpc->value,
            'duration_days' => 3,
            'title' => 'Titre',
            'destination_url' => 'https://boutique.cm',
            'format' => 'square_300x250',
            'media' => $fakePhp,
            'cities' => ['Yaoundé'],
        ])->assertSessionHasErrors('media');
    }

    public function test_advertiser_can_pause_and_resume_own_campaign_only(): void
    {
        $adv1 = User::query()->create([
            'name' => 'Adv 1',
            'email' => 'a1@cm.cm',
            'phone' => '+237677100100',
            'role' => UserRole::Advertiser,
            'password' => 'Secret1234',
        ]);
        $adv2 = User::query()->create([
            'name' => 'Adv 2',
            'email' => 'a2@cm.cm',
            'phone' => '+237677200200',
            'role' => UserRole::Advertiser,
            'password' => 'Secret1234',
        ]);

        $campaign = Campaign::query()->create([
            'advertiser_id' => $adv1->id,
            'name' => 'Campagne Active',
            'type' => CampaignType::Web,
            'billing_model' => BillingModel::Cpc,
            'status' => CampaignStatus::Active,
            'total_budget_xaf' => 15000,
            'unit_price_xaf' => 50,
        ]);

        $this->actingAs($adv1)->patch("/advertiser/campaigns/{$campaign->id}/toggle")->assertRedirect();
        $this->assertSame(CampaignStatus::Paused, $campaign->fresh()->status);

        $this->actingAs($adv1)->patch("/advertiser/campaigns/{$campaign->id}/toggle")->assertRedirect();
        $this->assertSame(CampaignStatus::Active, $campaign->fresh()->status);

        $this->actingAs($adv2)->patch("/advertiser/campaigns/{$campaign->id}/toggle")->assertForbidden();
    }

    public function test_advertiser_cannot_create_campaign_with_insufficient_wallet_balance(): void
    {
        $advertiser = User::query()->create([
            'name' => 'Annonceur Solde Faible',
            'email' => 'lowwallet@cm.cm',
            'phone' => '+237677999000',
            'role' => UserRole::Advertiser,
            'password' => 'Secret1234',
        ]);
        $advertiser->wallet()->updateOrCreate(
            ['user_id' => $advertiser->id],
            ['available_balance_xaf' => 5000]
        );

        $image = UploadedFile::fake()->image('promo.png', 300, 250)->size(10);

        // 5 jours × 2000 = 10000 FCFA > 5000 FCFA
        $this->actingAs($advertiser)->post('/advertiser/campaigns', [
            'name' => 'Campagne Sans Solde',
            'type' => CampaignType::Web->value,
            'billing_model' => BillingModel::Cpc->value,
            'duration_days' => 5,
            'title' => 'Offre test',
            'destination_url' => 'https://boutique.cm/offre',
            'format' => 'square_300x250',
            'media' => $image,
            'cities' => ['Douala'],
        ])->assertSessionHasErrors('wallet');

        $this->assertDatabaseCount('campaigns', 0);
    }

    public function test_advertiser_can_view_end_date_and_edit_campaign(): void
    {
        $adv = User::query()->create([
            'name' => 'Adv Edit',
            'email' => 'edit@cm.cm',
            'phone' => '+237677888999',
            'role' => UserRole::Advertiser,
            'password' => 'Secret1234',
        ]);
        $adv->wallet()->updateOrCreate(
            ['user_id' => $adv->id],
            ['available_balance_xaf' => 50000]
        );

        $otherAdv = User::query()->create([
            'name' => 'Other Adv',
            'email' => 'other@cm.cm',
            'phone' => '+237677888000',
            'role' => UserRole::Advertiser,
            'password' => 'Secret1234',
        ]);

        $startsAt = now();
        $campaign = Campaign::query()->create([
            'advertiser_id' => $adv->id,
            'name' => 'Campagne Initiale',
            'type' => CampaignType::Web,
            'billing_model' => BillingModel::Cpc,
            'status' => CampaignStatus::Active,
            'total_budget_xaf' => 10000,
            'daily_budget_xaf' => 2000,
            'unit_price_xaf' => 50,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addDays(5),
        ]);

        $campaign->creatives()->create([
            'title' => 'Titre Initial',
            'destination_url' => 'https://boutique.cm/old',
            'media_type' => 'image',
            'media_path' => 'creatives/demo.png',
            'format' => 'square_300x250',
            'file_size_bytes' => 1024,
        ]);

        $campaign->targeting()->create([
            'cities' => ['Douala'],
            'languages' => ['fr'],
            'devices' => ['mobile'],
            'categories' => ['actualites'],
        ]);

        // Liste affiche la date de fin et la durée
        $this->actingAs($adv)->get('/advertiser/campaigns')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Advertiser/Campaigns/Index')
                ->where('campaigns.data.0.ends_at', $startsAt->copy()->addDays(5)->format('d/m/Y H:i'))
                ->where('campaigns.data.0.duration_days', 5)
            );

        // Un autre annonceur ne peut pas modifier
        $this->actingAs($otherAdv)->get("/advertiser/campaigns/{$campaign->id}/edit")
            ->assertForbidden();

        // Propriétaire accède à l'édition
        $this->actingAs($adv)->get("/advertiser/campaigns/{$campaign->id}/edit")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Advertiser/Campaigns/Edit')
                ->where('campaign.name', 'Campagne Initiale')
            );

        // Modification de la campagne (durée passée à 10 jours + nouveau titre)
        $this->actingAs($adv)->put("/advertiser/campaigns/{$campaign->id}", [
            'name' => 'Campagne Modifiée',
            'duration_days' => 10,
            'title' => 'Nouveau Titre',
            'body_text' => 'Texte mis à jour',
            'destination_url' => 'https://boutique.cm/new',
            'format' => 'square_300x250',
            'cities' => ['Douala', 'Yaoundé'],
            'languages' => ['fr'],
            'devices' => ['mobile', 'desktop'],
            'categories' => ['business'],
        ])->assertRedirect(route('advertiser.campaigns.index'));

        $fresh = $campaign->fresh(['creatives', 'targeting']);
        $this->assertSame('Campagne Modifiée', $fresh->name);
        $this->assertSame(20000, $fresh->total_budget_xaf);
        $this->assertSame(CampaignStatus::PendingReview, $fresh->status);
        $this->assertSame('Nouveau Titre', $fresh->creatives->first()->title);
        $this->assertSame(['Douala', 'Yaoundé'], $fresh->targeting->cities);
    }
}
