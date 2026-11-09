<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'gabriel@rumahkucare.id'],
            [
                'name' => 'Gabriel',
                'password' => bcrypt('password'),
            ]
        );

        $this->call([
            VehicleModelSeeder::class,
            VehicleSeeder::class,
            HomeAssetSeeder::class,
        ]);
    }
}
