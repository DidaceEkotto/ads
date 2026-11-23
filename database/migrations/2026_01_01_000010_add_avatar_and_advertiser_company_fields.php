<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute la photo de profil sur users et les informations complètes d'entreprise
     * sur advertiser_profiles (logo, boîte postale, réseaux sociaux, description).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('locale');
        });

        Schema::table('advertiser_profiles', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('company_name');
            $table->string('postal_box', 80)->nullable()->after('city');
            $table->string('website_url')->nullable()->after('whatsapp_number');
            $table->string('facebook_url')->nullable()->after('website_url');
            $table->string('linkedin_url')->nullable()->after('facebook_url');
            $table->string('x_url')->nullable()->after('linkedin_url');
            $table->text('description')->nullable()->after('x_url');
        });
    }

    public function down(): void
    {
        Schema::table('advertiser_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'postal_box',
                'website_url',
                'facebook_url',
                'linkedin_url',
                'x_url',
                'description',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar_path');
        });
    }
};
