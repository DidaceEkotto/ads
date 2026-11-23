<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée les tables volumineuses de tracking (impressions, clicks) et d'audit anti-fraude.
     * Note : Pas de FK strictes sur impressions/clicks pour optimiser l'insertion et le partitionnement mensuel.
     */
    public function up(): void
    {
        Schema::create('impressions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('creative_id');
            $table->unsignedBigInteger('site_id')->nullable();
            $table->unsignedBigInteger('ad_zone_id')->nullable();
            $table->unsignedBigInteger('publisher_id');
            $table->char('visitor_hash', 64);
            $table->string('ip_address', 45);
            $table->string('city', 100)->nullable();
            $table->string('device_type', 20)->default('mobile');
            $table->boolean('is_adblock_detected')->default(false);
            $table->unsignedInteger('cost_xaf')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['campaign_id', 'created_at']);
            $table->index(['publisher_id', 'created_at']);
            $table->index(['ad_zone_id', 'created_at']);
            $table->index(['visitor_hash', 'campaign_id', 'created_at']);
        });

        Schema::create('clicks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('impression_id')->nullable();
            $table->unsignedBigInteger('tracking_link_id')->nullable();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('creative_id')->nullable();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->unsignedBigInteger('ad_zone_id')->nullable();
            $table->unsignedBigInteger('social_placement_id')->nullable();
            $table->unsignedBigInteger('publisher_id');
            $table->char('visitor_hash', 64);
            $table->string('ip_address', 45);
            $table->string('user_agent', 512)->nullable();
            $table->string('referer', 512)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('device_type', 20)->default('mobile');
            $table->boolean('is_valid')->default(true)->index();
            $table->unsignedTinyInteger('fraud_score')->default(0);
            $table->string('invalid_reason', 50)->nullable();
            $table->unsignedInteger('cost_xaf')->default(0);
            $table->unsignedInteger('publisher_earning_xaf')->default(0);
            $table->unsignedInteger('platform_fee_xaf')->default(0);
            $table->string('earning_status', 20)->default('pending'); // pending | available | rejected
            $table->timestamp('available_at')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['campaign_id', 'is_valid', 'created_at']);
            $table->index(['publisher_id', 'earning_status', 'available_at']);
            $table->index(['visitor_hash', 'campaign_id', 'created_at']);
        });

        Schema::create('fraud_flags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('click_id')->nullable()->index();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('publisher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 50)->index();
            $table->unsignedTinyInteger('score');
            $table->string('ip_address', 45);
            $table->json('details')->nullable();
            $table->boolean('is_resolved')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_flags');
        Schema::dropIfExists('clicks');
        Schema::dropIfExists('impressions');
    }
};
