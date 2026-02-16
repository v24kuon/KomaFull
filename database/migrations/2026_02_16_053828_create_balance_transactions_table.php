<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('unit')->comment('units: tickets, points');
            $table->integer('amount')->comment('positive for grant, negative for consume/refund');
            $table->string('transaction_type')->comment('types: grant, consume, refund, expire, adjust');
            $table->string('idempotency_key')->unique();
            $table->foreignId('prepaid_purchase_id')
                ->nullable()
                ->constrained('prepaid_purchases')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('reservation_id')
                ->nullable()
                ->constrained('reservations')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('stripe_reference_id')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_transactions');
    }
};
