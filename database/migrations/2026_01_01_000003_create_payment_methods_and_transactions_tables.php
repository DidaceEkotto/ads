<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée les tables des moyens de paiement (MTN MoMo / Orange Money)
     * et du grand livre financier immuable (transactions).
     */
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30); // mtn_momo | orange_money
            $table->string('account_number', 30); // +2376XXXXXXXX
            $table->string('account_name', 120);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'provider']);
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->foreignId('wallet_id')->constrained()->restrictOnDelete();
            $table->string('type', 30)->index(); // TransactionType
            $table->string('direction', 10); // credit | debit
            $table->unsignedBigInteger('amount_xaf');
            $table->unsignedBigInteger('balance_after_xaf');
            $table->string('status', 20)->default('completed')->index(); // pending | completed | failed | cancelled
            $table->string('gateway', 30)->nullable(); // saspay | manual
            $table->string('gateway_reference')->nullable()->index();
            $table->nullableMorphs('referenceable');
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['wallet_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('payment_methods');
    }
};
