<?php

namespace Database\Seeders;

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
        $beat = VehicleModel::where('brand', 'Honda')->where('model', 'Beat Street')->first();
        $avanza = VehicleModel::where('brand', 'Toyota')->where('model', 'Avanza 1.5 G')->first();
        $prado = VehicleModel::where('brand', 'Toyota')->where('model', 'Land Cruiser Prado')->first();

        if ($beat) {
            $vBeat = Vehicle::updateOrCreate(
                ['plate' => 'B 2341 KLS'],
                [
                    'vehicle_model_id' => $beat->id,
                    'year' => 2023,
                    'color_key' => 'black',
                    'odometer' => 8450,
                    'service_interval_km' => 5000,
                    'fuel_type' => 'Bensin',
                    'workshop' => 'AHASS Cipete',
                ]
            );

            if ($vBeat->serviceLogs()->count() === 0) {
                $vBeat->serviceLogs()->create([
                    'serviced_at' => now()->subMonths(2)->format('Y-m-d'),
                    'odometer' => 7000,
                    'notes' => 'Ganti oli mesin & kampas rem',
                ]);
            }
        }

        if ($avanza) {
            $vAvanza = Vehicle::updateOrCreate(
                ['plate' => 'B 1234 GAB'],
                [
                    'vehicle_model_id' => $avanza->id,
                    'year' => 2022,
                    'color_key' => 'black',
                    'odometer' => 44820,
                    'service_interval_km' => 5000,
                    'fuel_type' => 'Bensin',
                    'workshop' => 'Berkah Motor',
                ]
            );

            if ($vAvanza->serviceLogs()->count() === 0) {
                $vAvanza->serviceLogs()->create([
                    'serviced_at' => now()->subMonths(3)->format('Y-m-d'),
                    'odometer' => 40000,
                    'notes' => 'Servis berkala 40.000 KM & tune up',
                ]);
            }
        }

        if ($prado) {
            Vehicle::updateOrCreate(
                ['plate' => 'B 9999 KRA'],
                [
                    'vehicle_model_id' => $prado->id,
                    'year' => 2021,
                    'color_key' => 'white',
                    'odometer' => 68200,
                    'service_interval_km' => 5000,
                    'fuel_type' => 'Diesel',
                    'workshop' => 'Auto2000',
                ]
            );
        }
    }
}
