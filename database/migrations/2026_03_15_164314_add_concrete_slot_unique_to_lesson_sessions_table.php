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
        Schema::table('lesson_sessions', function (Blueprint $table) {
            $table->unique(
                ['program_id', 'location_id', 'staff_id', 'starts_at'],
                'lesson_sessions_concrete_slot_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_sessions', function (Blueprint $table) {
            $table->dropUnique('lesson_sessions_concrete_slot_unique');
        });
    }
};
