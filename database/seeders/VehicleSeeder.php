<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        $userId = $user?->id;

        $beat = VehicleModel::where('brand', 'Honda')->where('model', 'Beat Street')->first();
        $avanza = VehicleModel::where('brand', 'Toyota')->where('model', 'Avanza 1.5 G')->first();
        $prado = VehicleModel::where('brand', 'Toyota')->where('model', 'Land Cruiser Prado')->first();

        if ($beat) {
            $vBeat = Vehicle::updateOrCreate(
                ['plate' => 'B 2341 KLS'],
                [
                    'user_id' => $userId,
                    'vehicle_model_id' => $beat->id,
                    'year' => 2023,
                    'color_key' => 'black',
                    'odometer' => 8450,
                    'fuel_type' => 'Bensin',
                    'workshop' => 'AHASS Cipete',
                ]
            );

            if ($vBeat->maintenanceRecords()->count() === 0) {
                $vBeat->maintenanceRecords()->create([
                    'user_id' => $userId,
                    'done_at' => now()->subMonths(2)->format('Y-m-d'),
                    'odometer' => 7000,
                    'service_type' => 'Ganti Oli Mesin & Filter',
                    'workshop_name' => 'AHASS Cipete',
                    'notes' => 'Ganti oli mesin & kampas rem',
                ]);
            }

            if ($vBeat->schedules()->count() === 0) {
                $vBeat->schedules()->create([
                    'user_id' => $userId,
                    'title' => 'Ganti oli mesin',
                    'trigger_type' => 'kilometer',
                    'interval_km' => 3000,
                    'last_done_km' => 7000,
                    'next_due_km' => 10000,
                    'priority' => 'mendesak',
                    'is_active' => true,
                    'notes' => 'Ganti oli berkala tiap 3.000 KM.',
                ]);
            }
        }

        if ($avanza) {
            $vAvanza = Vehicle::updateOrCreate(
                ['plate' => 'B 1234 GAB'],
                [
                    'user_id' => $userId,
                    'vehicle_model_id' => $avanza->id,
                    'year' => 2022,
                    'color_key' => 'black',
                    'odometer' => 44820,
                    'fuel_type' => 'Bensin',
                    'workshop' => 'Berkah Motor',
                ]
            );

            if ($vAvanza->maintenanceRecords()->count() === 0) {
                $vAvanza->maintenanceRecords()->create([
                    'user_id' => $userId,
                    'done_at' => now()->subMonths(3)->format('Y-m-d'),
                    'odometer' => 40000,
                    'service_type' => 'Ganti Oli Mesin & Filter',
                    'workshop_name' => 'Berkah Motor',
                    'notes' => 'Servis berkala 40.000 KM & tune up',
                ]);
            }

            if ($vAvanza->schedules()->count() === 0) {
                $vAvanza->schedules()->create([
                    'user_id' => $userId,
                    'title' => 'Ganti oli mesin',
                    'trigger_type' => 'kilometer',
                    'interval_km' => 5000,
                    'last_done_km' => 40000,
                    'next_due_km' => 45000, // 44820 vs 45000 (diff 180 <= 200) -> Waktunya Servis!
                    'priority' => 'mendesak',
                    'is_active' => true,
                    'notes' => 'Servis oli mesin berkala tiap 5.000 KM.',
                ]);
            }
        }

        if ($prado) {
            Vehicle::updateOrCreate(
                ['plate' => 'B 9999 KRA'],
                [
                    'user_id' => $userId,
                    'vehicle_model_id' => $prado->id,
                    'year' => 2021,
                    'color_key' => 'white',
                    'odometer' => 68200,
                    'fuel_type' => 'Diesel',
                    'workshop' => 'Auto2000',
                ]
            );
        }
    }
}
