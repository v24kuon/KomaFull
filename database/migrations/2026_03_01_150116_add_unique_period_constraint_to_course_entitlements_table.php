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
        Schema::table('course_entitlements', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'course_plan_id', 'period_start', 'period_end'],
                'course_entitlements_user_plan_period_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_entitlements', function (Blueprint $table) {
            $table->dropUnique('course_entitlements_user_plan_period_unique');
        });
    }
};
