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
        Schema::table('inspection_run_items', function (Blueprint $table) {
            $table->foreignId('damage_report_id')->nullable()->after('status')->constrained('damage_reports')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inspection_run_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('damage_report_id');
        });
    }
};
