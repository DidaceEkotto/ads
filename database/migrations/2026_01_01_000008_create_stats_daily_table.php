<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table d'agrégation journalière pour éviter de requêter les tables brutes.
     */
    public function up(): void
    {
        Schema::create('stats_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('campaign_id')->default(0);
            $table->unsignedBigInteger('publisher_id')->default(0);
            $table->unsignedBigInteger('site_id')->default(0);
            $table->unsignedBigInteger('ad_zone_id')->default(0);
            $table->string('device_type', 20)->default('');
            $table->unsignedInteger('impressions_count')->default(0);
            $table->unsignedInteger('valid_clicks_count')->default(0);
            $table->unsignedInteger('invalid_clicks_count')->default(0);
            $table->unsignedInteger('adblock_hits_count')->default(0);
            $table->unsignedBigInteger('spend_xaf')->default(0);
            $table->unsignedBigInteger('publisher_earning_xaf')->default(0);
            $table->unsignedBigInteger('platform_fee_xaf')->default(0);
            $table->timestamps();

            $table->unique(
                ['date', 'campaign_id', 'publisher_id', 'site_id', 'ad_zone_id', 'device_type'],
                'stats_daily_composite_unique'
            );
            $table->index(['campaign_id', 'date']);
            $table->index(['publisher_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stats_daily');
    }
};
