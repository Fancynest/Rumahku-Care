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
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('subject');
            $table->string('title');
            $table->enum('trigger_type', ['tanggal', 'interval_hari', 'kilometer']);
            $table->unsignedInteger('interval_days')->nullable();
            $table->unsignedInteger('interval_km')->nullable();
            $table->date('next_due_date')->nullable();
            $table->unsignedInteger('next_due_km')->nullable();
            $table->date('last_done_at')->nullable();
            $table->unsignedInteger('last_done_km')->nullable();
            $table->enum('priority', ['normal', 'perhatian', 'mendesak', 'darurat'])->default('normal');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_schedules');
    }
};
