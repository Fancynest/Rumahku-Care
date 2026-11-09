<?php

namespace Database\Seeders;

use App\Models\VehicleModel;
use Illuminate\Database\Seeder;

class VehicleModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $models = [
            // Honda Motors
            [
                'brand' => 'Honda',
                'model' => 'Beat Street',
                'body_type' => 'motor_matic',
                'asset_image' => 'images/vehicles/honda-beat.webp',
                'asset_glb' => null,
            ],
            [
                'brand' => 'Honda',
                'model' => 'Vario 125',
                'body_type' => 'motor_matic',
                'asset_image' => null,
                'asset_glb' => null,
            ],
            [
                'brand' => 'Honda',
                'model' => 'Vario 160',
                'body_type' => 'motor_matic',
                'asset_image' => 'images/vehicles/honda-motorcycle.png',
                'asset_glb' => null,
            ],
            [
                'brand' => 'Honda',
                'model' => 'Scoopy',
                'body_type' => 'motor_matic',
                'asset_image' => null,
                'asset_glb' => null,
            ],
            [
                'brand' => 'Honda',
                'model' => 'CBR150R',
                'body_type' => 'motor_sport',
                'asset_image' => null,
                'asset_glb' => null,
            ],
            // Yamaha Motors
            [
                'brand' => 'Yamaha',
                'model' => 'NMAX 155',
                'body_type' => 'motor_matic',
                'asset_image' => null,
                'asset_glb' => null,
            ],
            [
                'brand' => 'Yamaha',
                'model' => 'Aerox 155',
                'body_type' => 'motor_matic',
                'asset_image' => null,
                'asset_glb' => null,
            ],
            [
                'brand' => 'Yamaha',
                'model' => 'YZF-R15',
                'body_type' => 'motor_sport',
                'asset_image' => null,
                'asset_glb' => null,
            ],
            // Toyota Cars
            [
                'brand' => 'Toyota',
                'model' => 'Avanza 1.5 G',
                'body_type' => 'mpv',
                'asset_image' => 'images/vehicles/toyota-avanza.png',
                'asset_glb' => null,
            ],
            [
                'brand' => 'Toyota',
                'model' => 'Innova Zenix',
                'body_type' => 'mpv',
                'asset_image' => null,
                'asset_glb' => null,
            ],
            [
                'brand' => 'Toyota',
                'model' => 'Land Cruiser Prado',
                'body_type' => 'suv',
                'asset_image' => 'images/vehicles/toyota-prado.webp',
                'asset_glb' => null,
            ],
            [
                'brand' => 'Toyota',
                'model' => 'Hilux D-Cab',
                'body_type' => 'pickup',
                'asset_image' => null,
                'asset_glb' => null,
            ],
            [
                'brand' => 'Toyota',
                'model' => 'Hiace Commuter',
                'body_type' => 'van',
                'asset_image' => null,
                'asset_glb' => null,
            ],
            // Honda Cars
            [
                'brand' => 'Honda',
                'model' => 'Brio Satya',
                'body_type' => 'hatchback',
                'asset_image' => null,
                'asset_glb' => null,
            ],
            [
                'brand' => 'Honda',
                'model' => 'Civic RS',
                'body_type' => 'sedan',
                'asset_image' => null,
                'asset_glb' => null,
            ],
            // Mitsubishi
            [
                'brand' => 'Mitsubishi',
                'model' => 'Xpander',
                'body_type' => 'mpv',
                'asset_image' => null,
                'asset_glb' => null,
            ],
            [
                'brand' => 'Mitsubishi',
                'model' => 'Canter',
                'body_type' => 'truk',
                'asset_image' => null,
                'asset_glb' => null,
            ],
        ];

        foreach ($models as $item) {
            VehicleModel::updateOrCreate(
                ['brand' => $item['brand'], 'model' => $item['model']],
                $item
            );
        }
    }
}
