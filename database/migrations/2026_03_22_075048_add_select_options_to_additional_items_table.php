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
        Schema::table('additional_items', function (Blueprint $table) {
            $table->json('select_options')->nullable()->after('digits');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('additional_items', function (Blueprint $table) {
            $table->dropColumn('select_options');
        });
    }
};
