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
        Schema::create('home_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('category', [
                'bangunan',
                'listrik',
                'air',
                'pendingin',
                'dapur',
                'kamar_mandi',
                'keamanan',
                'eksterior',
                'peralatan',
            ]);
            $table->string('room');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->date('purchased_at')->nullable();
            $table->date('last_inspected_at')->nullable();
            $table->enum('condition', ['normal', 'perlu_perhatian', 'rusak'])->default('normal');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_assets');
    }
};
