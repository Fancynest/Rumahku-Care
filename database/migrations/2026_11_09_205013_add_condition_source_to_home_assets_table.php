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
        Schema::table('home_assets', function (Blueprint $table) {
            $table->enum('condition_source', ['manual', 'damage'])->default('manual')->after('condition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('home_assets', function (Blueprint $table) {
            $table->dropColumn('condition_source');
        });
    }
};
