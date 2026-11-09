<?php

namespace Tests\Feature;

use App\Models\HomeAsset;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewTabsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Vehicle $vehicle;

    private HomeAsset $homeAsset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $vm = VehicleModel::create([
            'brand' => 'Toyota',
            'model' => 'Innova Zenix',
            'body_type' => 'mpv',
        ]);

        $this->vehicle = Vehicle::create([
            'user_id' => $this->user->id,
            'vehicle_model_id' => $vm->id,
            'plate' => 'B 1111 TAB',
            'year' => 2024,
            'color_key' => 'hitam',
            'odometer' => 15000,
            'fuel_type' => 'Hybrid',
        ]);

        $this->homeAsset = HomeAsset::create([
            'user_id' => $this->user->id,
            'name' => 'Genset Cadangan Rumah',
            'category' => 'listrik',
            'room' => 'Garasi Luar',
            'condition' => 'normal',
        ]);

        // Create sample data for each tab
        // 1. Jadwal
        $this->vehicle->schedules()->create([
            'user_id' => $this->user->id,
            'title' => 'Ganti Filter Udara',
            'trigger_type' => 'kilometer',
            'interval_km' => 10000,
            'next_due_km' => 20000,
            'priority' => 'normal',
            'is_active' => true,
        ]);

        // 2. Servis Record (dengan nota)
        $record = $this->vehicle->maintenanceRecords()->create([
            'user_id' => $this->user->id,
            'service_type' => 'Servis Berkala 10K',
            'done_at' => Carbon::today()->subMonths(2),
            'odometer' => 10000,
            'workshop_name' => 'Auto2000 Cilandak',
            'labor_cost' => 200000,
            'parts_cost' => 300000,
            'total_cost' => 500000,
        ]);

        // 3. Inspeksi
        $run = $this->vehicle->inspectionRuns()->create([
            'user_id' => $this->user->id,
            'template_key' => 'kendaraan_pra_jalan',
            'title' => 'Inspeksi Mudik Lebaran',
            'inspected_at' => Carbon::today()->subDays(5),
        ]);
        $run->items()->create([
            'item_label' => 'Tekanan Ban',
            'status' => 'ok',
        ]);

        // 4. Kerusakan
        $this->vehicle->damageReports()->create([
            'user_id' => $this->user->id,
            'title' => 'Baret Bemper Kiri',
            'severity' => 'ringan',
            'status' => 'dilaporkan',
            'reported_at' => Carbon::today()->subDays(1),
        ]);

        // 5. Dokumen
        $this->vehicle->documents()->create([
            'user_id' => $this->user->id,
            'type' => 'stnk_pajak',
            'title' => 'Pajak STNK 2026',
            'reference_number' => 'STNK-INNOVA-2026',
            'expires_at' => Carbon::today()->addMonths(6),
            'cost' => 4500000,
        ]);
    }

    public function test_vehicle_index_renders_all_tabs_and_components(): void
    {
        $response = $this->actingAs($this->user)->get(route('vehicles.index', ['v' => $this->vehicle->id]));

        $response->assertOk();
        $response->assertSee('Ringkasan');
        $response->assertSee('Jadwal');
        $response->assertSee('Inspeksi');
        $response->assertSee('Kerusakan');
        $response->assertSee('Dokumen');

        // Check content inside tabs
        $response->assertSee('B 1111 TAB');
        $response->assertSee('Ganti Filter Udara');
        $response->assertSee('Inspeksi Mudik Lebaran');
        $response->assertSee('Baret Bemper Kiri');
        $response->assertSee('Pajak STNK 2026');
        $response->assertSee('Auto2000 Cilandak');
    }

    public function test_home_asset_show_renders_all_tabs_and_components(): void
    {
        // Add sample schedule, inspection, damage, document for homeAsset
        $this->homeAsset->schedules()->create([
            'user_id' => $this->user->id,
            'title' => 'Pemanasan Mesin Genset',
            'trigger_type' => 'interval_hari',
            'interval_days' => 14,
            'next_due_date' => Carbon::today()->addDays(7),
            'priority' => 'normal',
            'is_active' => true,
        ]);

        $run = $this->homeAsset->inspectionRuns()->create([
            'user_id' => $this->user->id,
            'template_key' => 'rumah_keselamatan',
            'title' => 'Pemeriksaan Bahan Bakar Genset',
            'inspected_at' => Carbon::today(),
        ]);
        $run->items()->create([
            'item_label' => 'Level Solar',
            'status' => 'ok',
        ]);

        $this->homeAsset->damageReports()->create([
            'user_id' => $this->user->id,
            'title' => 'Aki Starter Soak',
            'severity' => 'sedang',
            'status' => 'dilaporkan',
            'reported_at' => Carbon::today(),
        ]);

        $this->homeAsset->documents()->create([
            'user_id' => $this->user->id,
            'type' => 'garansi',
            'title' => 'Garansi Genset Honda 1 Tahun',
            'expires_at' => Carbon::today()->addMonths(8),
        ]);

        $response = $this->actingAs($this->user)->get(route('home-assets.show', $this->homeAsset->id));

        $response->assertOk();
        $response->assertSee('Ringkasan');
        $response->assertSee('Jadwal');
        $response->assertSee('Inspeksi');
        $response->assertSee('Kerusakan');
        $response->assertSee('Dokumen');

        // Check content
        $response->assertSee('Genset Cadangan Rumah');
        $response->assertSee('Pemanasan Mesin Genset');
        $response->assertSee('Pemeriksaan Bahan Bakar Genset');
        $response->assertSee('Aki Starter Soak');
        $response->assertSee('Garansi Genset Honda 1 Tahun');
    }
}
