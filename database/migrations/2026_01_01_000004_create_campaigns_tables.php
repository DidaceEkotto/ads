<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée les tables des campagnes, créatives (image/GIF) et ciblages.
     */
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advertiser_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('type', 20)->index(); // web | social
            $table->string('billing_model', 20)->index(); // cpc | cpm | flat_post
            $table->string('status', 30)->default('draft')->index(); // CampaignStatus
            $table->unsignedBigInteger('total_budget_xaf');
            $table->unsignedBigInteger('daily_budget_xaf')->nullable();
            $table->unsignedBigInteger('spent_xaf')->default(0);
            $table->unsignedInteger('unit_price_xaf');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'type', 'starts_at', 'ends_at']);
        });

        Schema::create('creatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->text('body_text')->nullable();
            $table->string('destination_url', 512);
            $table->string('media_type', 20); // image | gif
            $table->string('media_path');
            $table->string('format', 30)->default('square_300x250'); // AdZoneFormat ou 'social'
            $table->unsignedInteger('file_size_bytes');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['campaign_id', 'format', 'is_active']);
        });

        Schema::create('campaign_targetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('cities')->nullable();
            $table->json('languages')->nullable();
            $table->json('devices')->nullable();
            $table->json('categories')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_targetings');
        Schema::dropIfExists('creatives');
        Schema::dropIfExists('campaigns');
    }
};
