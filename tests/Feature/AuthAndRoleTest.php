<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
    }

    public function test_advertiser_can_register_and_gets_profile_and_wallet(): void
    {
        $response = $this->post('/register', [
            'name' => 'Jean Kamga',
            'email' => 'jean@entreprise.cm',
            'phone' => '+237677112233',
            'role' => UserRole::Advertiser->value,
            'city' => 'Douala',
            'company_name' => 'Kamga SARL',
            'password' => 'Secret@1234',
            'password_confirmation' => 'Secret@1234',
            'accept_terms' => true,
        ]);

        $response->assertRedirect(route('advertiser.dashboard'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'jean@entreprise.cm')->firstOrFail();
        $this->assertSame(UserRole::Advertiser, $user->role);
        $this->assertNotNull($user->advertiserProfile);
        $this->assertNotNull($user->wallet);
        $this->assertSame(10000, $user->wallet->available_balance_xaf);
        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $user->wallet->id,
            'amount_xaf' => 10000,
            'direction' => 'credit',
            'status' => 'completed',
        ]);
    }

    public function test_publisher_can_register_and_cannot_access_advertiser_or_admin_routes(): void
    {
        $user = User::query()->create([
            'name' => 'Paul Editeur',
            'email' => 'paul@blog.cm',
            'phone' => '+237699445566',
            'role' => UserRole::Publisher,
            'locale' => 'fr',
            'password' => 'Secret1234',
        ]);
        Wallet::query()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get('/publisher/dashboard')
            ->assertOk();

        $this->actingAs($user)
            ->get('/advertiser/dashboard')
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_admin_can_access_livewire_admin_dashboard(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin CM',
            'email' => 'admin@cm-ads.cm',
            'phone' => '+237690000000',
            'role' => UserRole::Admin,
            'locale' => 'fr',
            'password' => 'Admin1234',
        ]);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('CM-Ads Admin');
    }

    public function test_locale_can_be_switched_between_fr_and_en(): void
    {
        $this->post('/locale/en')
            ->assertRedirect();

        $this->assertSame('en', session('locale'));
    }
}
