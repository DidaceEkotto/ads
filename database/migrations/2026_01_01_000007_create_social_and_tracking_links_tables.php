<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée les tables pour la diffusion semi-manuelle sur les réseaux sociaux.
     */
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publisher_id')->constrained('users')->cascadeOnDelete();
            $table->string('platform', 20)->index(); // facebook | x | linkedin
            $table->string('handle', 150);
            $table->string('profile_url', 512);
            $table->unsignedInteger('followers_count')->default(0);
            $table->string('verification_code', 32);
            $table->string('status', 20)->default('pending')->index(); // pending | approved | rejected
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'handle']);
        });

        Schema::create('social_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('publisher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('social_account_id')->constrained()->cascadeOnDelete();
            $table->string('post_url', 512)->nullable();
            $table->boolean('has_disclosure_tag')->default(false);
            $table->string('status', 30)->default('pending_proof')->index(); // SocialPlacementStatus
            $table->timestamp('published_at')->nullable();
            $table->timestamp('min_duration_ends_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'social_account_id']);
        });

        Schema::create('tracking_links', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique(); // Utilisé dans /c/{code}
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('publisher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('social_placement_id')->nullable()->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_links');
        Schema::dropIfExists('social_placements');
        Schema::dropIfExists('social_accounts');
    }
};
