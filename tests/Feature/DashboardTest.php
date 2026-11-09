<?php

namespace Tests\Feature;

use App\Models\HomeAsset;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $response->assertSee('Pengeluaran Bulan Ini');
        $response->assertSee('Rp 0');
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

    public function test_urgent_schedules_use_priority_as_tie_breaker_and_display_chip_only_when_not_normal(): void
    {
        $user = User::factory()->create(['name' => 'Eko Kurniawan']);
        $asset = HomeAsset::create([
            'user_id' => $user->id,
            'name' => 'Genset Cadangan',
            'category' => 'listrik',
            'room' => 'Garasi',
            'condition' => 'normal',
        ]);

        // Two schedules with same status (mendesak: 3 days left), but different priority
        $asset->schedules()->create([
            'user_id' => $user->id,
            'title' => 'Cek Oli Genset Normal',
            'trigger_type' => 'interval_hari',
            'interval_days' => 30,
            'next_due_date' => Carbon::today()->addDays(3)->toDateString(),
            'priority' => 'normal',
            'is_active' => true,
        ]);

        $asset->schedules()->create([
            'user_id' => $user->id,
            'title' => 'Cek Aki Genset Darurat',
            'trigger_type' => 'interval_hari',
            'interval_days' => 30,
            'next_due_date' => Carbon::today()->addDays(3)->toDateString(),
            'priority' => 'darurat',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);

        $content = $response->getContent();
        $posDarurat = strpos($content, 'Cek Aki Genset Darurat');
        $posNormal = strpos($content, 'Cek Oli Genset Normal');

        $this->assertNotFalse($posDarurat);
        $this->assertNotFalse($posNormal);
        $this->assertTrue($posDarurat < $posNormal, 'Schedule with higher priority darurat should appear before normal');

        // Priority darurat chip is rendered
        $response->assertSee('Darurat');
    }

    public function test_dashboard_displays_empty_state_with_call_to_action_and_buttons_when_user_has_no_data(): void
    {
        $user = User::factory()->create(['name' => 'Pengguna Baru']);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Mulai Kelola Rumah dan Kendaraan Anda');
        $response->assertSee('Tambah Kendaraan');
        $response->assertSee('Tambah Aset Rumah');
        $response->assertDontSee('kondisi prima', false);
        $response->assertDontSee('Semua Aset Terawat Baik');
    }

    public function test_schedules_query_is_memoized_between_dashboard_and_header_composer(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'plate' => 'B 7777 MEM',
            'odometer' => 5000,
            'color_key' => 'hitam',
            'fuel_type' => 'Bensin',
        ]);
        $vehicle->schedules()->create([
            'user_id' => $user->id,
            'title' => 'Cek Oli Rutin',
            'trigger_type' => 'kilometer',
            'interval_km' => 5000,
            'next_due_km' => 5100,
            'priority' => 'mendesak',
            'is_active' => true,
        ]);

        DB::enableQueryLog();

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);

        $queries = DB::getQueryLog();
        $scheduleQueries = array_filter($queries, function ($q) {
            return str_contains($q['query'], 'maintenance_schedules');
        });

        // The query to active schedules is executed only once across controller and header view composer
        $this->assertCount(1, $scheduleQueries);
    }

    public function test_app_service_provider_does_not_contain_has_table_check(): void
    {
        $code = (string) file_get_contents(app_path('Providers/AppServiceProvider.php'));
        $this->assertStringNotContainsString('hasTable', $code);
    }
}
