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
        if (User::count() === 0) {
            User::factory()->create([
                'name' => 'Gabriel',
                'email' => 'gabriel@example.com',
            ]);
        }

        $this->call([
            VehicleModelSeeder::class,
            VehicleSeeder::class,
        ]);
    }
}
