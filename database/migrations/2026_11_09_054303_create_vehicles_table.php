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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_model_id')->nullable()->constrained('vehicle_models')->nullOnDelete();
            $table->string('custom_model_name')->nullable();
            $table->string('custom_body_type')->nullable();
            $table->string('plate');
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('color_key')->default('black');
            $table->unsignedInteger('odometer')->default(0);
            $table->string('fuel_type')->default('Bensin');
            $table->string('workshop')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
