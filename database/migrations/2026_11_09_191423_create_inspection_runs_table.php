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
        Schema::create('inspection_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('inspectable'); // inspectable_type, inspectable_id
            $table->string('template_key', 50)->nullable();
            $table->string('title', 150);
            $table->date('inspected_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'inspected_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_runs');
    }
};
