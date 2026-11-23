<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée les tables des sites web éditeurs et de leurs emplacements publicitaires.
     */
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publisher_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('domain', 190)->unique();
            $table->string('category', 50)->index();
            $table->string('language', 5)->default('fr');
            $table->string('status', 20)->default('pending')->index(); // SiteStatus
            $table->string('verification_token', 64)->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ad_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('token', 32)->unique(); // Identifiant public neutre
            $table->string('format', 30)->index(); // AdZoneFormat
            $table->boolean('is_native')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_zones');
        Schema::dropIfExists('sites');
    }
};
