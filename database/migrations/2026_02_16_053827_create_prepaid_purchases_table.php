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
        Schema::create('prepaid_purchases', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('prepaid_product_id')
                ->constrained('prepaid_products')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->timestamp('purchased_at');
            $table->timestamp('expires_at')->nullable();
            $table->string('status')
                ->default('pending_payment')
                ->comment('statuses: pending_payment, processing, completed, expired, grant_failed');
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prepaid_purchases');
    }
};
