<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('inspection_run_items')
            ->where('status', 'bahaya')
            ->update(['status' => 'bermasalah']);

        Schema::table('inspection_run_items', function (Blueprint $table) {
            $table->string('status', 20)->default('ok')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('inspection_run_items')
            ->where('status', 'bermasalah')
            ->update(['status' => 'bahaya']);

        Schema::table('inspection_run_items', function (Blueprint $table) {
            $table->string('status', 20)->default('ok')->change();
        });
    }
};
