<?php

namespace Tests\Feature;

use App\Enums\SiteStatus;
use App\Enums\UserRole;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublisherSitesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
    }

    public function test_publisher_can_register_site_with_normalized_domain(): void
    {
        $publisher = User::query()->create([
            'name' => 'Editeur Site',
            'email' => 'site@cm.cm',
            'phone' => '+237699333444',
            'role' => UserRole::Publisher,
            'password' => 'Secret@1234',
        ]);

        $this->actingAs($publisher)->post('/publisher/sites', [
            'name' => 'Actu Cameroun',
            'domain' => 'https://www.ActuCameroun.cm/news',
            'category' => 'actualites',
            'language' => 'fr',
        ])->assertRedirect();

        $site = Site::query()->firstOrFail();
        $this->assertSame('actucameroun.cm', $site->domain);
        $this->assertSame(SiteStatus::Pending, $site->status);
    }

    public function test_publisher_can_view_own_site_and_cannot_create_manual_zones(): void
    {
        $pub1 = User::query()->create([
            'name' => 'Editeur A',
            'email' => 'ea@cm.cm',
            'phone' => '+237699444555',
            'role' => UserRole::Publisher,
            'password' => 'Secret@1234',
        ]);
        $pub2 = User::query()->create([
            'name' => 'Editeur B',
            'email' => 'eb@cm.cm',
            'phone' => '+237699555666',
            'role' => UserRole::Publisher,
            'password' => 'Secret@1234',
        ]);

        $site = Site::query()->create([
            'publisher_id' => $pub1->id,
            'name' => 'Tech CM',
            'domain' => 'tech.cm',
            'category' => 'tech',
            'language' => 'fr',
            'status' => SiteStatus::Approved,
            'site_token' => 'techcmsitetoken12345',
        ]);

        // Les emplacements manuels ne sont plus créables par l'éditeur
        $this->actingAs($pub1)->post("/publisher/sites/{$site->id}/zones", [
            'name' => 'Header',
            'format' => 'banner_728x90',
        ])->assertNotFound();

        $this->actingAs($pub1)->get("/publisher/sites/{$site->id}")->assertOk();

        // Isolation par SitePolicy
        $this->actingAs($pub2)->get("/publisher/sites/{$site->id}")->assertForbidden();
    }
}
