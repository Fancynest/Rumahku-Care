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
            Vehicle::updateOrCreate(
                ['plate' => 'B 2341 KLS'],
                [
                    'vehicle_model_id' => $beat->id,
                    'year' => 2023,
                    'color_key' => 'black',
                    'odometer' => 8450,
                    'fuel_type' => 'Bensin',
                    'workshop' => 'AHASS Cipete',
                ]
            );
        }

        if ($avanza) {
            Vehicle::updateOrCreate(
                ['plate' => 'B 1234 GAB'],
                [
                    'vehicle_model_id' => $avanza->id,
                    'year' => 2022,
                    'color_key' => 'black',
                    'odometer' => 44820,
                    'fuel_type' => 'Bensin',
                    'workshop' => 'Berkah Motor',
                ]
            );
        }

        if ($prado) {
            Vehicle::updateOrCreate(
                ['plate' => 'B 9999 KRA'],
                [
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
