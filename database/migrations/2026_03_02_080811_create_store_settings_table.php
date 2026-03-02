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
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('program_label')->default('プログラム');
            $table->string('session_label')->default('セッション');
            $table->string('staff_label')->default('スタッフ');
            $table->string('location_label')->default('店舗');
            $table->unsignedInteger('reserve_deadline_minutes')->default(60);
            $table->unsignedInteger('cancel_deadline_minutes')->default(1440);
            $table->unsignedInteger('withdrawal_deadline_days')->default(30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
