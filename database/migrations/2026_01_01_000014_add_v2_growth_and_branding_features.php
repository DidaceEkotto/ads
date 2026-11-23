<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute le parrainage (affiliation), le proxy first-party CNAME et les conversions CPA.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 16)->nullable()->unique()->after('avatar_path');
            $table->foreignId('referred_by')->nullable()->after('referral_code')->constrained('users')->nullOnDelete();
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->string('custom_cdn_host', 255)->nullable()->after('allowed_formats');
        });

        Schema::create('conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id')->index();
            $table->unsignedBigInteger('click_id')->nullable()->index();
            $table->char('visitor_hash', 64)->index();
            $table->string('event_name', 60)->default('purchase');
            $table->unsignedInteger('value_xaf')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversions');

        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('custom_cdn_host');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by');
            $table->dropColumn('referral_code');
        });
    }
};
