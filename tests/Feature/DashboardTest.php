<?php

namespace Tests\Feature;

use App\Models\HomeAsset;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_greeting_and_summary_numbers_from_db(): void
    {
        $user = User::factory()->create(['name' => 'Gabriel Pratama', 'email' => 'gabriel@example.com']);

        // 1 Vehicle in prime condition
        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'plate' => 'B 1111 TST',
            'odometer' => 1000,
            'color_key' => 'hitam',
            'fuel_type' => 'Bensin',
        ]);
        $vehicle->schedules()->create([
            'user_id' => $user->id,
            'title' => 'Ganti Oli 5000 KM',
            'trigger_type' => 'kilometer',
            'interval_km' => 5000,
            'next_due_km' => 6000,
            'priority' => 'normal',
            'is_active' => true,
        ]);

        // 2 Home assets in normal condition
        $asset1 = HomeAsset::create([
            'user_id' => $user->id,
            'name' => 'AC Kamar',
            'category' => 'pendingin',
            'room' => 'Kamar Utama',
            'condition' => 'normal',
        ]);
        $asset2 = HomeAsset::create([
            'user_id' => $user->id,
            'name' => 'Pompa Air Jet',
            'category' => 'air',
            'room' => 'Belakang',
            'condition' => 'normal',
        ]);

        // 1 Urgent schedule (mendesak: <= 7 days)
        $asset1->schedules()->create([
            'user_id' => $user->id,
            'title' => 'Cuci Filter AC Rutin',
            'trigger_type' => 'interval_hari',
            'interval_days' => 90,
            'next_due_date' => Carbon::today()->addDays(3)->toDateString(),
            'priority' => 'mendesak',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Halo Gabriel');
        $response->assertSee('ada 1 hal yang perlu perhatianmu minggu ini');
        $response->assertSee('Cuci Filter AC Rutin');
        $response->assertSee('3'); // 1 prime vehicle + 2 normal home assets = 3
    }

    public function test_dashboard_displays_max_three_urgent_cards(): void
    {
        $user = User::factory()->create(['name' => 'Budi Santoso']);
        $asset = HomeAsset::create([
            'user_id' => $user->id,
            'name' => 'AC Ruang Kerja',
            'category' => 'pendingin',
            'room' => 'Lantai 2',
            'condition' => 'normal',
        ]);

        // Create 4 schedules: 1 terlambat, 1 mendesak, 1 perhatian, 1 normal
        $asset->schedules()->create([
            'user_id' => $user->id,
            'title' => 'Tugas Terlambat',
            'trigger_type' => 'interval_hari',
            'interval_days' => 30,
            'next_due_date' => Carbon::today()->subDays(2)->toDateString(),
            'priority' => 'darurat',
            'is_active' => true,
        ]);

        $asset->schedules()->create([
            'user_id' => $user->id,
            'title' => 'Tugas Mendesak',
            'trigger_type' => 'interval_hari',
            'interval_days' => 30,
            'next_due_date' => Carbon::today()->addDays(4)->toDateString(),
            'priority' => 'mendesak',
            'is_active' => true,
        ]);

        $asset->schedules()->create([
            'user_id' => $user->id,
            'title' => 'Tugas Perhatian',
            'trigger_type' => 'interval_hari',
            'interval_days' => 30,
            'next_due_date' => Carbon::today()->addDays(20)->toDateString(),
            'priority' => 'perhatian',
            'is_active' => true,
        ]);

        $asset->schedules()->create([
            'user_id' => $user->id,
            'title' => 'Tugas Normal Jauh',
            'trigger_type' => 'interval_hari',
            'interval_days' => 90,
            'next_due_date' => Carbon::today()->addDays(80)->toDateString(),
            'priority' => 'normal',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Tugas Terlambat');
        $response->assertSee('Tugas Mendesak');
        $response->assertSee('Tugas Perhatian');
        $response->assertDontSee('Tugas Normal Jauh');
    }
}
