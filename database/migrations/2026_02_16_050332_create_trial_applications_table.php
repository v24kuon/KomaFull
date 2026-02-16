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
        Schema::create('trial_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('lesson_session_id')
                ->constrained('lesson_sessions')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('payment_method');
            $table->string('status')->default('pending_payment');
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('reservation_id')
                ->nullable()
                ->constrained('reservations')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->timestamp('refunded_at')->nullable();
            $table->text('refund_reason')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trial_applications');
    }
};
