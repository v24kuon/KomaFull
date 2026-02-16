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
        Schema::create('course_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('stripe_price_id')->nullable()->index();
            $table->unsignedInteger('usage_count');
            $table->string('allocation_type')->default('total')->comment('allocation_types: total, per_category');
            $table->string('level')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('active')->comment('statuses: active, inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_plans');
    }
};
