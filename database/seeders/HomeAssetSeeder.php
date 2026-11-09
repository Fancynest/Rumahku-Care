<?php

namespace Database\Seeders;

use App\Models\HomeAsset;
use App\Models\User;
use Illuminate\Database\Seeder;

class HomeAssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        if (! $user) {
            return;
        }

        $assets = [
            [
                'name' => 'AC Ruang Tamu',
                'category' => 'pendingin',
                'room' => 'Ruang Tamu',
                'brand' => 'Daikin',
                'model' => 'Flash Inverter 1 PK',
                'condition' => 'normal',
                'purchased_at' => '2024-01-15',
                'last_inspected_at' => '2026-08-10',
                'notes' => 'Pembersihan filter rutin setiap 3 bulan.',
            ],
            [
                'name' => 'Toren Air 1000 Liter',
                'category' => 'air',
                'room' => 'Dak Atap',
                'brand' => 'Penguin',
                'model' => 'TB 110',
                'condition' => 'perlu_perhatian',
                'purchased_at' => '2023-05-10',
                'last_inspected_at' => '2026-05-10',
                'notes' => 'Mulai terlihat endapan lumut tipis di dasar toren.',
            ],
            [
                'name' => 'Pompa Air Pendorong',
                'category' => 'air',
                'room' => 'Halaman Belakang',
                'brand' => 'Shimizu',
                'model' => 'PS-135 E',
                'condition' => 'normal',
                'purchased_at' => '2023-06-20',
                'last_inspected_at' => '2026-08-01',
                'notes' => 'Tekanan air stabil.',
            ],
            [
                'name' => 'Exhaust Fan Dapur',
                'category' => 'dapur',
                'room' => 'Dapur',
                'brand' => 'Panasonic',
                'model' => 'FV-25RUM',
                'condition' => 'normal',
                'purchased_at' => '2024-02-12',
                'last_inspected_at' => '2026-09-01',
                'notes' => 'Bilah kipas bersih dari minyak.',
            ],
        ];

        foreach ($assets as $assetData) {
            HomeAsset::updateOrCreate(
                ['user_id' => $user->id, 'name' => $assetData['name']],
                $assetData
            );
        }
    }
}
