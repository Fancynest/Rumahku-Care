<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaultUserId = DB::table('users')->value('id');
        if (! $defaultUserId) {
            $defaultUserId = DB::table('users')->insertGetId([
                'name' => 'Gabriel',
                'email' => 'gabriel@rumahkucare.id',
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('vehicles', function (Blueprint $table) use ($defaultUserId) {
            $table->foreignId('user_id')->default($defaultUserId)->after('id')->constrained()->cascadeOnDelete();
        });

        DB::table('vehicles')->whereNull('user_id')->orWhere('user_id', 0)->update(['user_id' => $defaultUserId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
