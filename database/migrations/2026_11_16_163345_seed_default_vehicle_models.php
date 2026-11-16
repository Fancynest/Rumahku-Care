<?php

use Database\Seeders\VehicleModelSeeder;
use Illuminate\Database\Migrations\Migration;
use App\Models\VehicleModel;

return new class extends Migration
{
    public function up(): void
    {
        if (VehicleModel::count() === 0) {
            (new VehicleModelSeeder)->run();
        }
    }

    public function down(): void
    {
        // Data seeding migration, no rollback needed
    }
};
