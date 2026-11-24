<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_slot_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('slot_key', 40);
            $table->string('device_type', 20)->default('desktop');
            $table->unsignedBigInteger('impressions_count')->default(0);
            $table->unsignedBigInteger('viewable_count')->default(0);
            $table->unsignedBigInteger('clicks_count')->default(0);
            $table->unsignedBigInteger('earnings_xaf')->default(0);
            $table->timestamps();

            $table->unique(['site_id', 'slot_key', 'device_type'], 'ad_slot_metrics_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_slot_metrics');
    }
};
