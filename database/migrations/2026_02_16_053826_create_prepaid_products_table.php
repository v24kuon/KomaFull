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
        Schema::create('prepaid_products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('prepaid_type')->comment('prepaid_types: tickets, points');
            $table->string('sales_name');
            $table->unsignedInteger('usage_count');
            $table->unsignedInteger('expires_in_days');
            $table->unsignedInteger('price')->default(0);
            $table->string('status')->default('active')->comment('statuses: active, inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prepaid_products');
    }
};
