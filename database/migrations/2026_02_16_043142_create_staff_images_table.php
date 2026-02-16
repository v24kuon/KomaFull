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
        Schema::create('staff_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')
                ->constrained('staffs')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('path');
            $table->string('image_type');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_images');
    }
};
