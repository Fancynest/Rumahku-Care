<?php

use App\Models\Vehicle;
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
        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('subject');
            $table->foreignId('schedule_id')->nullable()->constrained('maintenance_schedules')->nullOnDelete();
            $table->date('done_at');
            $table->unsignedInteger('odometer')->nullable();
            $table->string('service_type');
            $table->string('workshop_name')->nullable();
            $table->unsignedBigInteger('labor_cost')->default(0);
            $table->unsignedBigInteger('parts_cost')->default(0);
            $table->text('parts')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 1. Migrate existing service_logs to maintenance_records
        if (Schema::hasTable('service_logs')) {
            $logs = DB::table('service_logs')->get();
            foreach ($logs as $log) {
                $vehicle = DB::table('vehicles')->where('id', $log->vehicle_id)->first();
                $userId = $vehicle?->user_id ?? DB::table('users')->value('id');

                DB::table('maintenance_records')->insert([
                    'user_id' => $userId,
                    'subject_type' => Vehicle::class,
                    'subject_id' => $log->vehicle_id,
                    'schedule_id' => null,
                    'done_at' => substr($log->serviced_at, 0, 10),
                    'odometer' => $log->odometer,
                    'service_type' => 'Ganti Oli Mesin & Filter',
                    'workshop_name' => $vehicle?->workshop ?? 'Bengkel',
                    'labor_cost' => 0,
                    'parts_cost' => 0,
                    'parts' => null,
                    'notes' => $log->notes,
                    'created_at' => $log->created_at ?? now(),
                    'updated_at' => $log->updated_at ?? now(),
                ]);
            }
        }

        // 2. For each vehicle, create 1 KM schedule "Ganti oli mesin"
        $vehicles = DB::table('vehicles')->get();
        foreach ($vehicles as $v) {
            $latestRecord = DB::table('maintenance_records')
                ->where('subject_type', Vehicle::class)
                ->where('subject_id', $v->id)
                ->orderByDesc('done_at')
                ->orderByDesc('id')
                ->first();

            $intervalKm = property_exists($v, 'service_interval_km') && $v->service_interval_km ? $v->service_interval_km : 5000;
            $lastDoneKm = $latestRecord?->odometer;
            $nextDueKm = $lastDoneKm ? ($lastDoneKm + $intervalKm) : ($v->odometer + $intervalKm);

            DB::table('maintenance_schedules')->insert([
                'user_id' => $v->user_id,
                'subject_type' => Vehicle::class,
                'subject_id' => $v->id,
                'title' => 'Ganti oli mesin',
                'trigger_type' => 'kilometer',
                'interval_km' => $intervalKm,
                'interval_days' => null,
                'next_due_date' => null,
                'next_due_km' => $nextDueKm,
                'last_done_at' => $latestRecord?->done_at,
                'last_done_km' => $lastDoneKm,
                'priority' => 'mendesak',
                'is_active' => true,
                'notes' => 'Servis oli mesin berkala sesuai jarak tempuh.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Drop service_logs table
        Schema::dropIfExists('service_logs');

        // 4. Drop service_interval_km column from vehicles
        if (Schema::hasColumn('vehicles', 'service_interval_km')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->dropColumn('service_interval_km');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->unsignedInteger('service_interval_km')->default(5000)->after('odometer');
        });

        Schema::create('service_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->date('serviced_at');
            $table->unsignedInteger('odometer');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('maintenance_records');
    }
};
