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
        Schema::create('inspection_run_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_run_id')->constrained()->cascadeOnDelete();
            $table->string('item_label', 150);
            $table->enum('status', ['ok', 'perhatian', 'bahaya'])->default('ok');
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index(['inspection_run_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_run_items');
    }
};
