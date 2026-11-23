<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Peuple la base de données avec les paramètres initiaux et un compte admin.
     */
    public function run(): void
    {
        $this->call(SettingSeeder::class);

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@cm-ads.cm'],
            [
                'name' => 'Administrateur',
                'phone' => '+237690000000',
                'role' => UserRole::Admin,
                'locale' => 'fr',
                'is_active' => true,
                'password' => 'Admin@2026!',
                'email_verified_at' => now(),
            ]
        );

        Wallet::query()->firstOrCreate(['user_id' => $admin->id]);
    }
}
