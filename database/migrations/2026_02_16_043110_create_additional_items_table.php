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
        Schema::create('additional_items', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('additional_item_type')->comment('additional_item_types: text, number, select, checkbox');
            $table->string('label_name');
            $table->string('input_type')->comment('input_types: text, number, select, checkbox');
            $table->unsignedInteger('digits')->nullable();
            $table->string('status')->default('active')->comment('statuses: active, inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('additional_items');
    }
};
