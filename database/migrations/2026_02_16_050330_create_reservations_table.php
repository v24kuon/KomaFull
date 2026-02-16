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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('lesson_session_id')
                ->constrained('lesson_sessions')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('seat_bucket');
            $table->string('payment_method');
            $table->string('status')->default('confirmed');
            $table->unsignedInteger('ticket_cost')->default(0);
            $table->unsignedInteger('point_cost')->default(0);
            $table->unsignedBigInteger('course_entitlement_id')->nullable()->index();
            $table->timestamp('canceled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->index(['user_id', 'lesson_session_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
