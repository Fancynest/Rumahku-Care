<?php

namespace Tests\Feature;

use App\Models\DamageReport;
use App\Models\Document;
use App\Models\FuelLog;
use App\Models\HomeAsset;
use App\Models\InspectionRun;
use App\Models\MaintenanceRecord;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Vehicle $vehicle;

    protected HomeAsset $homeAsset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->vehicle = Vehicle::create([
            'user_id' => $this->user->id,
            'plate' => 'B 9999 HIS',
            'odometer' => 20000,
            'color_key' => 'hitam',
            'fuel_type' => 'Bensin',
        ]);
        $this->homeAsset = HomeAsset::create([
            'user_id' => $this->user->id,
            'name' => 'Genset Cadangan',
            'category' => 'listrik',
            'room' => 'Garasi',
            'condition' => 'normal',
        ]);
    }

    public function test_user_can_view_history_page_with_empty_state(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('schedules.history'));

        $response->assertOk();
        $response->assertViewIs('schedules.history');
        $response->assertSee('Riwayat Aktivitas Terpadu');
        $response->assertSee('Tidak Ada Riwayat Aktivitas');
    }

    public function test_activity_timeline_merges_all_five_sources_in_descending_order(): void
    {
        $this->actingAs($this->user);

        // 1. Perawatan (2026-11-01)
        MaintenanceRecord::create([
            'user_id' => $this->user->id,
            'subject_type' => Vehicle::class,
            'subject_id' => $this->vehicle->id,
            'service_type' => 'Ganti Busi Platina',
            'done_at' => '2026-11-01',
            'cost' => 120000,
            'odometer' => 19500,
        ]);

        // 2. BBM (2026-11-03)
        FuelLog::create([
            'user_id' => $this->user->id,
            'vehicle_id' => $this->vehicle->id,
            'filled_at' => '2026-11-03',
            'odometer' => 19700,
            'liters' => 25.0,
            'total_cost' => 250000,
            'fuel_grade' => 'Pertamax',
            'is_full_tank' => true,
        ]);

        // 3. Inspeksi (2026-11-05)
        InspectionRun::create([
            'user_id' => $this->user->id,
            'inspectable_type' => HomeAsset::class,
            'inspectable_id' => $this->homeAsset->id,
            'title' => 'Inspeksi Triwulan Genset',
            'inspected_at' => '2026-11-05',
        ]);

        // 4. Dokumen (2026-11-07)
        Document::create([
            'user_id' => $this->user->id,
            'subject_type' => Vehicle::class,
            'subject_id' => $this->vehicle->id,
            'type' => 'stnk_pajak',
            'title' => 'Pembayaran PKB Tahunan',
            'issued_at' => '2026-11-07',
            'cost' => 850000,
        ]);

        $response = $this->get(route('schedules.history'));

        $response->assertOk();
        $response->assertSee('Pembayaran PKB Tahunan');
        $response->assertSee('Inspeksi Triwulan Genset');
        $response->assertSee('Isi BBM Pertamax');
        $response->assertSee('Ganti Busi Platina');

        // Pastikan urutan teks di HTML: Pembayaran PKB (07) lebih dulu dari Ganti Busi (01)
        $content = $response->getContent();
        $posDoc = strpos($content, 'Pembayaran PKB Tahunan');
        $posInsp = strpos($content, 'Inspeksi Triwulan Genset');
        $posFuel = strpos($content, 'Isi BBM Pertamax');
        $posMaint = strpos($content, 'Ganti Busi Platina');

        $this->assertTrue($posDoc < $posInsp);
        $this->assertTrue($posInsp < $posFuel);
        $this->assertTrue($posFuel < $posMaint);
    }

    public function test_damage_report_produces_two_distinct_events_when_resolved(): void
    {
        $this->actingAs($this->user);

        DamageReport::create([
            'user_id' => $this->user->id,
            'reportable_type' => HomeAsset::class,
            'reportable_id' => $this->homeAsset->id,
            'title' => 'Kabel Dinamo Terkelupas',
            'severity' => 'sedang',
            'status' => 'selesai',
            'reported_at' => '2026-11-02 10:00:00',
            'resolved_at' => '2026-11-06 14:00:00',
            'repair_cost' => 175000,
            'repair_notes' => 'Sudah diganti selongsong kabel baru',
        ]);

        $response = $this->get(route('schedules.history'));

        $response->assertOk();
        $response->assertSee('Laporan Kerusakan: Kabel Dinamo Terkelupas');
        $response->assertSee('Perbaikan Selesai: Kabel Dinamo Terkelupas');

        // Perbaikan selesai (06 Nov) harus muncul lebih atas daripada laporan kerusakan (02 Nov)
        $content = $response->getContent();
        $posRes = strpos($content, 'Perbaikan Selesai: Kabel Dinamo Terkelupas');
        $posRep = strpos($content, 'Laporan Kerusakan: Kabel Dinamo Terkelupas');

        $this->assertTrue($posRes < $posRep);
    }

    public function test_unresolved_damage_report_only_produces_reported_event(): void
    {
        $this->actingAs($this->user);

        DamageReport::create([
            'user_id' => $this->user->id,
            'reportable_type' => Vehicle::class,
            'reportable_id' => $this->vehicle->id,
            'title' => 'Baret Bumper Depan Kanan',
            'severity' => 'ringan',
            'status' => 'dilaporkan',
            'reported_at' => '2026-11-04 09:00:00',
            'resolved_at' => null,
        ]);

        $response = $this->get(route('schedules.history'));

        $response->assertOk();
        $response->assertSee('Laporan Kerusakan: Baret Bumper Depan Kanan');
        $response->assertDontSee('Perbaikan Selesai: Baret Bumper Depan Kanan');
    }

    public function test_filter_by_type_works(): void
    {
        $this->actingAs($this->user);

        FuelLog::create([
            'user_id' => $this->user->id,
            'vehicle_id' => $this->vehicle->id,
            'filled_at' => '2026-11-04',
            'odometer' => 19800,
            'liters' => 30.0,
            'total_cost' => 300000,
            'fuel_grade' => 'Pertalite',
            'is_full_tank' => true,
        ]);

        MaintenanceRecord::create([
            'user_id' => $this->user->id,
            'subject_type' => Vehicle::class,
            'subject_id' => $this->vehicle->id,
            'service_type' => 'Servis Rem Belakang',
            'done_at' => '2026-11-04',
            'cost' => 200000,
            'odometer' => 19800,
        ]);

        // Filter hanya BBM
        $response = $this->get(route('schedules.history', ['type' => 'fuel']));
        $response->assertOk();
        $response->assertSee('Isi BBM Pertalite');
        $response->assertDontSee('Servis Rem Belakang');

        // Filter hanya Perawatan
        $response2 = $this->get(route('schedules.history', ['type' => 'maintenance']));
        $response2->assertOk();
        $response2->assertDontSee('Isi BBM Pertalite');
        $response2->assertSee('Servis Rem Belakang');
    }

    public function test_filter_by_subject_works(): void
    {
        $this->actingAs($this->user);

        InspectionRun::create([
            'user_id' => $this->user->id,
            'inspectable_type' => Vehicle::class,
            'inspectable_id' => $this->vehicle->id,
            'title' => 'Inspeksi Ban Kendaraan',
            'inspected_at' => '2026-11-05',
        ]);

        InspectionRun::create([
            'user_id' => $this->user->id,
            'inspectable_type' => HomeAsset::class,
            'inspectable_id' => $this->homeAsset->id,
            'title' => 'Inspeksi Arus Listrik Genset',
            'inspected_at' => '2026-11-05',
        ]);

        // Saring hanya Kendaraan
        $responseVeh = $this->get(route('schedules.history', ['subject' => "vehicle:{$this->vehicle->id}"]));
        $responseVeh->assertOk();
        $responseVeh->assertSee('Inspeksi Ban Kendaraan');
        $responseVeh->assertDontSee('Inspeksi Arus Listrik Genset');

        // Saring hanya Aset Rumah
        $responseAsset = $this->get(route('schedules.history', ['subject' => "home_asset:{$this->homeAsset->id}"]));
        $responseAsset->assertOk();
        $responseAsset->assertDontSee('Inspeksi Ban Kendaraan');
        $responseAsset->assertSee('Inspeksi Arus Listrik Genset');
    }

    public function test_filter_by_date_range_works(): void
    {
        $this->actingAs($this->user);

        FuelLog::create([
            'user_id' => $this->user->id,
            'vehicle_id' => $this->vehicle->id,
            'filled_at' => '2026-11-01',
            'odometer' => 19000,
            'liters' => 20.0,
            'total_cost' => 200000,
            'fuel_grade' => 'Bensin Awal',
            'is_full_tank' => true,
        ]);

        FuelLog::create([
            'user_id' => $this->user->id,
            'vehicle_id' => $this->vehicle->id,
            'filled_at' => '2026-11-05',
            'odometer' => 19500,
            'liters' => 20.0,
            'total_cost' => 200000,
            'fuel_grade' => 'Bensin Tengah',
            'is_full_tank' => true,
        ]);

        FuelLog::create([
            'user_id' => $this->user->id,
            'vehicle_id' => $this->vehicle->id,
            'filled_at' => '2026-11-10',
            'odometer' => 19900,
            'liters' => 20.0,
            'total_cost' => 200000,
            'fuel_grade' => 'Bensin Akhir',
            'is_full_tank' => true,
        ]);

        $response = $this->get(route('schedules.history', [
            'from' => '2026-11-03',
            'to' => '2026-11-07',
        ]));

        $response->assertOk();
        $response->assertDontSee('Bensin Awal');
        $response->assertSee('Bensin Tengah');
        $response->assertDontSee('Bensin Akhir');
    }

    public function test_pagination_limits_to_20_per_page(): void
    {
        $this->actingAs($this->user);

        for ($i = 1; $i <= 25; $i++) {
            $dayStr = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            FuelLog::create([
                'user_id' => $this->user->id,
                'vehicle_id' => $this->vehicle->id,
                'filled_at' => "2026-10-{$dayStr}",
                'odometer' => 10000 + ($i * 100),
                'liters' => 10.0,
                'total_cost' => 100000,
                'fuel_grade' => "Batch BBM #{$i}",
                'is_full_tank' => true,
            ]);
        }

        // Halaman 1: harus memuat Batch BBM #25 (terbaru) s/d #6 (20 item)
        $responseP1 = $this->get(route('schedules.history', ['page' => 1]));
        $responseP1->assertOk();
        $responseP1->assertSee('Batch BBM #25');
        $responseP1->assertDontSee('Batch BBM #5');

        // Halaman 2: harus memuat Batch BBM #5 s/d #1 (sisa 5 item)
        $responseP2 = $this->get(route('schedules.history', ['page' => 2]));
        $responseP2->assertOk();
        $responseP2->assertSee('Batch BBM #5');
        $responseP2->assertDontSee('Batch BBM #25');
    }

    public function test_data_isolation_between_users(): void
    {
        $otherUser = User::factory()->create();
        $otherVehicle = Vehicle::create([
            'user_id' => $otherUser->id,
            'plate' => 'L 4444 PRIV',
            'odometer' => 30000,
            'color_key' => 'putih',
            'fuel_type' => 'Bensin',
        ]);

        MaintenanceRecord::create([
            'user_id' => $otherUser->id,
            'subject_type' => Vehicle::class,
            'subject_id' => $otherVehicle->id,
            'service_type' => 'Servis Mesin Rahasia User Lain',
            'done_at' => '2026-11-05',
            'cost' => 990000,
            'odometer' => 30000,
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('schedules.history'));
        $response->assertOk();
        $response->assertDontSee('Servis Mesin Rahasia User Lain');
    }
}
