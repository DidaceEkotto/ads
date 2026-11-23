<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée les tables des profils (annonceurs, éditeurs) et des portefeuilles (XAF).
     */
    public function up(): void
    {
        Schema::create('advertiser_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name')->nullable();
            $table->string('business_sector', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('whatsapp_number', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('publisher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('display_name');
            $table->string('city', 100)->nullable();
            $table->string('whatsapp_number', 20)->nullable();
            $table->unsignedTinyInteger('custom_share_percent')->nullable(); // Surcharge du taux par défaut (ex: 70%)
            $table->timestamps();
        });

        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('available_balance_xaf')->default(0);
            $table->unsignedBigInteger('pending_balance_xaf')->default(0);
            $table->unsignedBigInteger('total_earned_xaf')->default(0);
            $table->unsignedBigInteger('total_spent_xaf')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('publisher_profiles');
        Schema::dropIfExists('advertiser_profiles');
    }
};
