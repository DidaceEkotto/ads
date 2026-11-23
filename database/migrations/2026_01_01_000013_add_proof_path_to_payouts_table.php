<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute le chemin de la capture d'écran (preuve de paiement) sur payouts.
     */
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->string('proof_path')->nullable()->after('gateway_reference');
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropColumn('proof_path');
        });
    }
};
