<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute la géolocalisation (pays & ville) aux tables de suivi.
     */
    public function up(): void
    {
        Schema::table('impressions', function (Blueprint $table) {
            $table->string('country', 80)->default('Cameroun')->after('ip_address');
            $table->index(['campaign_id', 'country', 'city']);
        });

        Schema::table('clicks', function (Blueprint $table) {
            $table->string('country', 80)->default('Cameroun')->after('ip_address');
            $table->index(['campaign_id', 'country', 'city']);
        });
    }

    public function down(): void
    {
        Schema::table('clicks', function (Blueprint $table) {
            $table->dropIndex(['campaign_id', 'country', 'city']);
            $table->dropColumn('country');
        });

        Schema::table('impressions', function (Blueprint $table) {
            $table->dropIndex(['campaign_id', 'country', 'city']);
            $table->dropColumn('country');
        });
    }
};
