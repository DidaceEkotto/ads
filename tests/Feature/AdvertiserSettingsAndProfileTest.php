<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdvertiserSettingsAndProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
        Storage::fake('public');
    }

    public function test_advertiser_can_update_company_settings_with_logo(): void
    {
        $adv = User::query()->create([
            'name' => 'Jean Kamga',
            'email' => 'jk@cm.cm',
            'phone' => '+237677000123',
            'role' => UserRole::Advertiser,
            'password' => 'Secret@1234',
        ]);
        Wallet::query()->create(['user_id' => $adv->id]);

        $logo = UploadedFile::fake()->image('logo.png', 200, 200)->size(40);

        $this->actingAs($adv)->post('/advertiser/settings', [
            'company_name' => 'Kamga Tech SARL',
            'business_sector' => 'Tech',
            'city' => 'Douala',
            'postal_box' => 'BP 4567 Douala',
            'whatsapp_number' => '+237677000123',
            'website_url' => 'https://kamgatech.cm',
            'facebook_url' => 'https://facebook.com/kamgatech',
            'description' => 'Solutions numériques au Cameroun.',
            'logo' => $logo,
        ])->assertRedirect();

        $profile = $adv->advertiserProfile->fresh();
        $this->assertSame('Kamga Tech SARL', $profile->company_name);
        $this->assertSame('BP 4567 Douala', $profile->postal_box);
        $this->assertNotNull($profile->logo_path);
        Storage::disk('public')->assertExists($profile->logo_path);
    }

    public function test_advertiser_can_update_profile_avatar_and_password_with_complexity(): void
    {
        $adv = User::query()->create([
            'name' => 'Jean Kamga',
            'email' => 'jk2@cm.cm',
            'phone' => '+237677000456',
            'role' => UserRole::Advertiser,
            'password' => 'Secret@1234',
        ]);

        $avatar = UploadedFile::fake()->image('avatar.jpg', 150, 150)->size(30);

        $this->actingAs($adv)->post('/advertiser/profile', [
            'name' => 'Jean Kamga Pro',
            'email' => 'jk2@cm.cm',
            'phone' => '+237677000456',
            'avatar' => $avatar,
        ])->assertRedirect();

        $adv->refresh();
        $this->assertSame('Jean Kamga Pro', $adv->name);
        $this->assertNotNull($adv->avatar_path);
        Storage::disk('public')->assertExists($adv->avatar_path);

        // Mot de passe faible sans caractère spécial (@,_,-) -> rejeté
        $this->actingAs($adv)->put('/advertiser/password', [
            'current_password' => 'Secret@1234',
            'password' => 'Weak1234',
            'password_confirmation' => 'Weak1234',
        ])->assertSessionHasErrors('password');

        // Mot de passe fort valide -> accepté
        $this->actingAs($adv)->put('/advertiser/password', [
            'current_password' => 'Secret@1234',
            'password' => 'Strong_2026',
            'password_confirmation' => 'Strong_2026',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('Strong_2026', $adv->fresh()->password));
    }
}
