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
        Schema::create('fuel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->date('filled_at');
            $table->unsignedInteger('odometer');
            $table->decimal('liters', 8, 2);
            $table->unsignedBigInteger('total_cost');
            $table->string('fuel_grade', 50)->nullable();
            $table->boolean('is_full_tank')->default(true);
            $table->string('station', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'filled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_logs');
    }
};
