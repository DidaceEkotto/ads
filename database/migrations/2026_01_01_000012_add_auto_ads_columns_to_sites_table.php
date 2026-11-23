<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute la configuration Auto-Ads (script unique dans <head>) sur la table sites.
     */
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->string('site_token', 32)->nullable()->unique()->after('verification_token');
            $table->boolean('auto_ads_enabled')->default(true)->after('site_token');
            $table->json('allowed_formats')->nullable()->after('auto_ads_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['site_token', 'auto_ads_enabled', 'allowed_formats']);
        });
    }
};
