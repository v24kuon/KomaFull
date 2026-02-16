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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')
                ->unique()
                ->constrained('reservations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('attendance_status');
            $table->timestamp('marked_at');
            $table->foreignId('marked_by_staff_id')
                ->nullable()
                ->constrained('staffs')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
