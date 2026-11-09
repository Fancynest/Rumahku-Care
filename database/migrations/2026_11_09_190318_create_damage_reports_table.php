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
        Schema::create('damage_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('reportable'); // reportable_type, reportable_id
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->enum('severity', ['ringan', 'sedang', 'berat'])->default('ringan');
            $table->enum('status', ['dilaporkan', 'dalam_perbaikan', 'selesai'])->default('dilaporkan');
            $table->date('reported_at');
            $table->date('resolved_at')->nullable();
            $table->string('technician_name', 100)->nullable();
            $table->unsignedInteger('repair_cost')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('damage_reports');
    }
};
