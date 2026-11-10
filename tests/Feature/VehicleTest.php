<?php

namespace Tests\Feature;

use App\Models\HomeAsset;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use App\Services\AttentionFeed;
use App\Services\ExpenseReport;
use App\Services\ScheduleCalendar;
use App\Services\ScheduleStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $user = User::first();
        if ($user) {
            $this->actingAs($user);
        }
    }

    public function test_can_view_vehicles_list_with_seeded_data(): void
    {
        $response = $this->get('/vehicles');

        $response->assertStatus(200);
        $response->assertSee('Kendaraan Saya');
        $response->assertSee('B 2341 KLS');
        $response->assertSee('B 1234 GAB');
        $response->assertSee('B 9999 KRA');
        $response->assertSee('Warna:');
    }

    public function test_can_create_vehicle_from_catalog(): void
    {
        $model = VehicleModel::where('brand', 'Honda')->where('model', 'Beat Street')->firstOrFail();

        $response = $this->post('/vehicles', [
            'plate' => 'D 9988 XYZ',
            'vehicle_model_id' => (string) $model->id,
            'year' => 2024,
            'odometer' => 1500,
            'color_key' => 'red',
            'fuel_type' => 'Bensin',
            'workshop' => 'AHASS Dago',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('vehicles', [
            'plate' => 'D 9988 XYZ',
            'vehicle_model_id' => $model->id,
            'odometer' => 1500,
            'color_key' => 'red',
        ]);
    }

    public function test_can_create_vehicle_with_other_custom_model_and_body_type(): void
    {
        $response = $this->post('/vehicles', [
            'plate' => 'B 7777 ABC',
            'vehicle_model_id' => 'other',
            'custom_model_name' => 'Vespa Sprint 150',
            'custom_body_type' => 'motor_matic',
            'year' => 2023,
            'odometer' => 4500,
            'color_key' => 'blue',
            'fuel_type' => 'Bensin',
            'workshop' => 'Vespa Care',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('vehicles', [
            'plate' => 'B 7777 ABC',
            'vehicle_model_id' => null,
            'custom_model_name' => 'Vespa Sprint 150',
            'custom_body_type' => 'motor_matic',
        ]);
    }

    public function test_cannot_create_vehicle_without_required_fields(): void
    {
        $response = $this->post('/vehicles', []);

        $response->assertSessionHasErrors(['plate', 'vehicle_model_id', 'odometer', 'color_key', 'fuel_type']);
    }

    public function test_cannot_create_vehicle_with_invalid_model_id(): void
    {
        $response = $this->post('/vehicles', [
            'plate' => 'B 1234 ABC',
            'vehicle_model_id' => '999999',
            'odometer' => 1000,
            'color_key' => 'hitam',
            'fuel_type' => 'Bensin',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['vehicle_model_id']);
    }

    public function test_can_update_vehicle_odometer(): void
    {
        $vehicle = Vehicle::firstOrFail();

        $response = $this->patch("/vehicles/{$vehicle->id}/odometer", [
            'odometer' => $vehicle->odometer + 500,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'odometer' => $vehicle->odometer + 500,
        ]);
    }

    public function test_can_update_vehicle_odometer_with_formatted_strings(): void
    {
        $vehicle = Vehicle::firstOrFail();
        $targetOdometer = $vehicle->odometer + 1000;

        // Uji format "15.000,00"
        $formatted = number_format($targetOdometer, 2, ',', '.');
        $response = $this->patch("/vehicles/{$vehicle->id}/odometer", [
            'odometer' => $formatted,
        ]);

        $response->assertRedirect();
        $this->assertSame($targetOdometer, $vehicle->fresh()->odometer);

        // Uji format "15.500,-"
        $targetOdometer2 = $targetOdometer + 500;
        $formatted2 = number_format($targetOdometer2, 0, ',', '.').',-';
        $response2 = $this->patch("/vehicles/{$vehicle->id}/odometer", [
            'odometer' => $formatted2,
        ]);

        $response2->assertRedirect();
        $this->assertSame($targetOdometer2, $vehicle->fresh()->odometer);
    }

    public function test_cannot_update_vehicle_odometer_lower_than_current(): void
    {
        $vehicle = Vehicle::firstOrFail();

        $response = $this->patch("/vehicles/{$vehicle->id}/odometer", [
            'odometer' => $vehicle->odometer - 50,
        ]);

        $response->assertSessionHasErrors(['odometer']);
        $this->assertEquals(
            'Odometer baru tidak boleh lebih kecil dari odometer kendaraan saat ini ('.number_format($vehicle->odometer).' KM).',
            session('errors')->first('odometer')
        );
    }

    public function test_vehicle_service_status_based_on_schedules(): void
    {
        $user = User::firstOrFail();
        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'plate' => 'B 3333 TST',
            'odometer' => 10000,
            'color_key' => 'hitam',
            'fuel_type' => 'Bensin',
        ]);

        // Belum ada jadwal
        $this->assertSame('Belum ada jadwal', $vehicle->status_label);

        // Buat jadwal KM: target 15000 KM (selisih 5000 KM > 500) -> Kondisi Prima
        $schedule = $vehicle->schedules()->create([
            'user_id' => $user->id,
            'title' => 'Ganti oli mesin',
            'trigger_type' => 'kilometer',
            'interval_km' => 5000,
            'next_due_km' => 15000,
            'priority' => 'mendesak',
            'is_active' => true,
        ]);
        $vehicle->refresh();
        $this->assertSame('Kondisi Prima', $vehicle->status_label);

        // Odometer naik jadi 14900 KM (selisih 100 <= 200) -> Waktunya Servis
        $vehicle->update(['odometer' => 14900]);
        $vehicle->refresh();
        $this->assertSame('Waktunya Servis', $vehicle->status_label);
    }

    public function test_can_store_service_log_for_vehicle(): void
    {
        $vehicle = Vehicle::firstOrFail();

        $response = $this->post("/vehicles/{$vehicle->id}/service-logs", [
            'serviced_at' => '2026-11-09',
            'odometer' => $vehicle->odometer + 100,
            'notes' => 'Ganti oli transmisi dan filter',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('maintenance_records', [
            'subject_id' => $vehicle->id,
            'subject_type' => Vehicle::class,
            'done_at' => '2026-11-09 00:00:00',
            'odometer' => $vehicle->odometer + 100,
            'notes' => 'Ganti oli transmisi dan filter',
        ]);

        $this->assertEquals($vehicle->odometer + 100, $vehicle->fresh()->odometer);
    }

    public function test_can_store_service_log_with_full_details_and_total_cost(): void
    {
        $vehicle = Vehicle::firstOrFail();

        $response = $this->post("/vehicles/{$vehicle->id}/service-logs", [
            'done_at' => '2026-11-09',
            'odometer' => $vehicle->odometer + 500,
            'service_type' => 'Ganti Oli',
            'workshop_name' => 'Bengkel Resmi Auto2000',
            'labor_cost' => 150000,
            'parts_cost' => 350000,
            'parts' => 'Oli TMO 4L, Filter Oli',
            'notes' => 'Servis berkala rutin',
        ]);

        $response->assertRedirect();

        $record = $vehicle->maintenanceRecords()->latest('id')->first();
        $this->assertNotNull($record);
        $this->assertSame(150000, $record->labor_cost);
        $this->assertSame(350000, $record->parts_cost);
        $this->assertSame(500000, $record->total_cost);
        $this->assertSame('Bengkel Resmi Auto2000', $record->workshop_name);
        $this->assertEquals($vehicle->odometer + 500, $vehicle->fresh()->odometer);
    }

    public function test_storing_service_log_marks_linked_schedule_complete(): void
    {
        $vehicle = Vehicle::firstOrFail();
        $user = $vehicle->user;

        $schedule = $vehicle->schedules()->create([
            'user_id' => $user->id,
            'title' => 'Ganti filter AC',
            'trigger_type' => 'kilometer',
            'interval_km' => 10000,
            'next_due_km' => $vehicle->odometer + 10000,
            'priority' => 'normal',
            'is_active' => true,
        ]);

        $newOdo = $vehicle->odometer + 1000;

        $response = $this->post("/vehicles/{$vehicle->id}/service-logs", [
            'done_at' => '2026-11-09',
            'odometer' => $newOdo,
            'service_type' => 'Ganti filter AC',
            'schedule_id' => $schedule->id,
            'labor_cost' => 50000,
            'parts_cost' => 120000,
        ]);

        $response->assertRedirect();

        $schedule->refresh();
        $this->assertSame('2026-11-09', $schedule->last_done_at?->format('Y-m-d'));
        $this->assertSame($newOdo, $schedule->last_done_km);
        $this->assertSame($newOdo + 10000, $schedule->next_due_km);
    }

    public function test_storing_service_log_with_date_schedule_deactivates_it(): void
    {
        $vehicle = Vehicle::firstOrFail();
        $user = $vehicle->user;

        $schedule = $vehicle->schedules()->create([
            'user_id' => $user->id,
            'title' => 'Uji Emisi Satu Kali',
            'trigger_type' => 'tanggal',
            'next_due_date' => '2026-11-01',
            'priority' => 'mendesak',
            'is_active' => true,
        ]);

        $response = $this->post("/vehicles/{$vehicle->id}/service-logs", [
            'done_at' => '2026-11-09',
            'odometer' => $vehicle->odometer + 100,
            'service_type' => 'Uji Emisi Satu Kali',
            'schedule_id' => $schedule->id,
        ]);

        $response->assertRedirect();

        $schedule->refresh();
        $this->assertFalse((bool) $schedule->is_active);
        $this->assertSame('2026-11-09', $schedule->last_done_at?->format('Y-m-d'));
    }

    public function test_can_backfill_historical_service_log_without_decreasing_vehicle_odometer(): void
    {
        $vehicle = Vehicle::firstOrFail();
        $initialOdometer = $vehicle->odometer;

        // Record a historical service from earlier with a lower odometer
        $response = $this->post("/vehicles/{$vehicle->id}/service-logs", [
            'done_at' => Carbon::yesterday()->toDateString(),
            'odometer' => max(100, $initialOdometer - 1000),
            'service_type' => 'Ganti Oli Sejarah',
            'notes' => 'Servis masa lalu',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('maintenance_records', [
            'subject_id' => $vehicle->id,
            'odometer' => max(100, $initialOdometer - 1000),
            'service_type' => 'Ganti Oli Sejarah',
        ]);

        // Vehicle current odometer must NOT decrease
        $this->assertSame($initialOdometer, $vehicle->fresh()->odometer);
    }

    public function test_cannot_store_service_log_with_future_date(): void
    {
        $vehicle = Vehicle::firstOrFail();

        $response = $this->post("/vehicles/{$vehicle->id}/service-logs", [
            'done_at' => Carbon::tomorrow()->toDateString(),
            'odometer' => $vehicle->odometer + 100,
        ]);

        $response->assertSessionHasErrors(['done_at']);
    }

    public function test_cannot_store_service_log_with_odometer_conflicting_with_records_timeline(): void
    {
        $user = User::firstOrFail();
        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'plate' => 'B 9900 TML',
            'odometer' => 20000,
            'color_key' => 'hitam',
            'fuel_type' => 'Bensin',
        ]);

        // Record 1: 2026-01-01 -> 5000 KM
        $vehicle->maintenanceRecords()->create([
            'user_id' => $user->id,
            'done_at' => '2026-01-01',
            'odometer' => 5000,
            'service_type' => 'Servis 1',
        ]);

        // Record 2: 2026-06-01 -> 10000 KM
        $vehicle->maintenanceRecords()->create([
            'user_id' => $user->id,
            'done_at' => '2026-06-01',
            'odometer' => 10000,
            'service_type' => 'Servis 2',
        ]);

        // Trying to record at 2026-03-01 with 4000 KM (lower than 2026-01-01's 5000 KM) -> Fails
        $responseLow = $this->post("/vehicles/{$vehicle->id}/service-logs", [
            'done_at' => '2026-03-01',
            'odometer' => 4000,
        ]);
        $responseLow->assertSessionHasErrors(['odometer']);

        // Trying to record at 2026-03-01 with 12000 KM (higher than 2026-06-01's 10000 KM) -> Fails
        $responseHigh = $this->post("/vehicles/{$vehicle->id}/service-logs", [
            'done_at' => '2026-03-01',
            'odometer' => 12000,
        ]);
        $responseHigh->assertSessionHasErrors(['odometer']);

        // Valid backfill between: 2026-03-01 with 7500 KM -> Succeeds
        $responseValid = $this->post("/vehicles/{$vehicle->id}/service-logs", [
            'done_at' => '2026-03-01',
            'odometer' => 7500,
        ]);
        $responseValid->assertRedirect();
        $this->assertSame(20000, $vehicle->fresh()->odometer); // Vehicle odometer remains 20000
    }

    public function test_cannot_store_service_log_with_invalid_data(): void
    {
        $vehicle = Vehicle::firstOrFail();

        $response = $this->post("/vehicles/{$vehicle->id}/service-logs", []);

        $response->assertSessionHasErrors(['done_at', 'odometer']);
    }

    public function test_can_update_vehicle_details_and_model_from_catalog(): void
    {
        $user = User::firstOrFail();
        $this->actingAs($user);
        $vehicle = $user->vehicles()->firstOrFail();

        $newModel = VehicleModel::where('brand', 'Toyota')->where('model', 'Avanza 1.5 G')->firstOrFail();

        $response = $this->put("/vehicles/{$vehicle->id}", [
            'plate' => 'B 8888 EDIT',
            'vehicle_model_id' => (string) $newModel->id,
            'year' => 2023,
            'color_key' => 'putih',
            'fuel_type' => 'Bensin',
            'workshop' => 'Auto2000 Tebet',
        ]);

        $response->assertRedirect();
        $fresh = $vehicle->fresh();
        $this->assertSame('B 8888 EDIT', $fresh->plate);
        $this->assertSame($newModel->id, $fresh->vehicle_model_id);
        $this->assertSame(2023, $fresh->year);
        $this->assertSame('putih', $fresh->color_key);
        $this->assertSame('Auto2000 Tebet', $fresh->workshop);

        // User lain tidak boleh mengedit kendaraan ini (404)
        $this->flushSession();
        $userB = User::factory()->create();
        $this->actingAs($userB);
        $responseForbidden = $this->put("/vehicles/{$vehicle->id}", [
            'plate' => 'B 9999 HACK',
            'vehicle_model_id' => (string) $newModel->id,
            'color_key' => 'hitam',
            'fuel_type' => 'Bensin',
        ]);
        $responseForbidden->assertStatus(404);
    }

    public function test_cannot_update_vehicle_with_duplicate_plate_for_same_user(): void
    {
        $user = User::firstOrFail();
        $this->actingAs($user);
        $vehicles = $user->vehicles()->take(2)->get();
        $this->assertCount(2, $vehicles);

        $v1 = $vehicles[0];
        $v2 = $vehicles[1];

        $response = $this->put("/vehicles/{$v2->id}", [
            'plate' => $v1->plate,
            'vehicle_model_id' => (string) ($v2->vehicle_model_id ?? 'other'),
            'color_key' => 'hitam',
            'fuel_type' => 'Bensin',
        ]);

        $response->assertSessionHasErrors(['plate']);
    }

    public function test_plate_is_normalized_and_duplicate_with_case_or_whitespace_is_rejected(): void
    {
        $user = User::firstOrFail();
        $this->actingAs($user);

        // Buat kendaraan awal dengan plat "B 1234 XYZ"
        Vehicle::create([
            'user_id' => $user->id,
            'plate' => 'B 1234 XYZ',
            'odometer' => 1000,
            'color_key' => 'hitam',
            'fuel_type' => 'Bensin',
        ]);

        // Coba buat kendaraan baru dengan variasi huruf kecil dan spasi berlebih "  b   1234   xyz  "
        $responseStoreDuplicate = $this->post('/vehicles', [
            'plate' => '  b   1234   xyz  ',
            'vehicle_model_id' => 'other',
            'custom_model_name' => 'Custom Motor',
            'custom_body_type' => 'motor_matic',
            'odometer' => 500,
            'color_key' => 'putih',
            'fuel_type' => 'Bensin',
        ]);
        $responseStoreDuplicate->assertSessionHasErrors(['plate']);

        // Buat kendaraan baru dengan plat unik tapi formatnya acak -> harus dinormalisasi
        $responseStoreValid = $this->post('/vehicles', [
            'plate' => '  d   7890   klm  ',
            'vehicle_model_id' => 'other',
            'custom_model_name' => 'Custom Motor 2',
            'custom_body_type' => 'motor_matic',
            'odometer' => 500,
            'color_key' => 'putih',
            'fuel_type' => 'Bensin',
        ]);
        $responseStoreValid->assertRedirect();
        $this->assertDatabaseHas('vehicles', [
            'user_id' => $user->id,
            'plate' => 'D 7890 KLM',
        ]);

        $createdVehicle = Vehicle::where('plate', 'D 7890 KLM')->firstOrFail();

        // Update plat ke variasi huruf kecil dari plat pertama "b 1234 xyz" -> harus ditolak
        $responseUpdateDuplicate = $this->put("/vehicles/{$createdVehicle->id}", [
            'plate' => 'b 1234 xyz',
            'vehicle_model_id' => 'other',
            'custom_model_name' => 'Custom Motor 2',
            'custom_body_type' => 'motor_matic',
            'color_key' => 'putih',
            'fuel_type' => 'Bensin',
        ]);
        $responseUpdateDuplicate->assertSessionHasErrors(['plate']);
    }

    public function test_can_archive_and_restore_vehicle_and_observes_feed_and_calendar_effects(): void
    {
        $user = User::firstOrFail();
        $this->actingAs($user);
        $vehicle = $user->vehicles()->firstOrFail();

        // Buat jadwal mendesak dan dokumen jatuh tempo pada kendaraan ini
        $schedule = $vehicle->schedules()->create([
            'user_id' => $user->id,
            'title' => 'Uji Emisi Kendaraan',
            'trigger_type' => 'tanggal',
            'interval_months' => 6,
            'next_due_date' => Carbon::today()->addDays(2)->toDateString(),
            'priority' => 'mendesak',
            'is_active' => true,
        ]);

        $document = $vehicle->documents()->create([
            'user_id' => $user->id,
            'type' => 'pajak_tahunan',
            'title' => 'Pajak STNK Tahunan',
            'cost' => 500000,
            'expires_at' => Carbon::today()->addDays(5)->toDateString(),
        ]);

        // Verifikasi sebelum arsip: muncul di AttentionFeed
        $feed = app(AttentionFeed::class);
        $urgentBefore = $feed->urgent($user);
        $this->assertTrue($urgentBefore->contains(fn ($i) => $i->id === $schedule->id));

        // 1. Arsipkan kendaraan
        $archiveResponse = $this->post("/vehicles/{$vehicle->id}/archive", [
            'archive_reason' => 'dijual',
        ]);
        $archiveResponse->assertRedirect();

        $freshVehicle = $vehicle->fresh();
        $this->assertTrue($freshVehicle->isArchived());
        $this->assertSame('dijual', $freshVehicle->archive_reason);
        $this->assertStringContainsString('(arsip)', $freshVehicle->name);

        // 2. Efek ke Pemilih Garasi:
        // Default /vehicles hanya memuat kendaraan aktif
        $viewActiveResponse = $this->get('/vehicles');
        $viewActiveResponse->assertStatus(200);
        $viewActiveResponse->assertDontSee($freshVehicle->plate);

        // /vehicles?archived=1 memuat kendaraan terarsip
        $viewArchivedResponse = $this->get('/vehicles?archived=1');
        $viewArchivedResponse->assertStatus(200);
        $viewArchivedResponse->assertSee($freshVehicle->plate);
        $viewArchivedResponse->assertSee('(arsip)');

        // 3. Efek ke Bell / AttentionFeed & Dashboard:
        // Item jadwal/dokumen kendaraan terarsip tidak boleh muncul
        app()->forgetInstance(AttentionFeed::class);
        app()->forgetInstance(ScheduleStatus::class);
        $urgentAfter = app(AttentionFeed::class)->urgent($user);
        $this->assertFalse($urgentAfter->contains(fn ($i) => $i->id === $schedule->id));
        $this->assertFalse($urgentAfter->contains(fn ($i) => $i->id === $document->id));

        // 4. Efek ke Kalender:
        $calendar = app(ScheduleCalendar::class);
        $calData = $calendar->build($user, Carbon::today()->format('Y-m'));
        $agendaItems = collect($calData['agenda_list']);
        $this->assertFalse($agendaItems->contains(fn ($item) => $item->id === $schedule->id));

        // 5. Efek ke Keuangan: Laporan keuangan TETAP memuat biaya dokumen/BBM dengan label (arsip)
        $expenseReport = app(ExpenseReport::class);
        $expenses = $expenseReport->getAllExpenses($user);
        $docExpense = $expenses->firstWhere('origin_id', $document->id);
        $this->assertNotNull($docExpense);
        $this->assertStringContainsString('(arsip)', $docExpense->subject_label);

        // 6. Pulihkan kendaraan
        $restoreResponse = $this->post("/vehicles/{$vehicle->id}/restore");
        $restoreResponse->assertRedirect();

        $restoredVehicle = $vehicle->fresh();
        $this->assertFalse($restoredVehicle->isArchived());
        $this->assertNull($restoredVehicle->archive_reason);
        $this->assertStringNotContainsString('(arsip)', $restoredVehicle->name);
    }

    public function test_permanent_delete_cascades_all_relations_and_physical_attachment_files(): void
    {
        Storage::fake('local');

        $user = User::firstOrFail();
        $this->actingAs($user);
        $vehicle = $user->vehicles()->create([
            'plate' => 'B 7777 DEL',
            'odometer' => 1000,
            'color_key' => 'hitam',
            'fuel_type' => 'Bensin',
        ]);

        // Upload lampiran fisik pada kendaraan
        $fileVehicle = UploadedFile::fake()->image('mobil.jpg', 600, 600);
        $pathVehicle = $fileVehicle->store('attachments/'.$user->id, 'local');
        $attVehicle = $vehicle->attachments()->create([
            'user_id' => $user->id,
            'path' => $pathVehicle,
            'original_name' => 'mobil.jpg',
            'mime' => 'image/jpeg',
            'size' => 1024,
        ]);

        // Buat relasi anak: maintenance record dengan lampiran fisik nota
        $record = $vehicle->maintenanceRecords()->create([
            'user_id' => $user->id,
            'done_at' => Carbon::today()->toDateString(),
            'odometer' => 1200,
            'service_type' => 'Ganti Oli',
            'labor_cost' => 50000,
        ]);
        $fileNota = UploadedFile::fake()->create('nota.pdf', 500, 'application/pdf');
        $pathNota = $fileNota->store('attachments/'.$user->id, 'local');
        $attRecord = $record->attachments()->create([
            'user_id' => $user->id,
            'path' => $pathNota,
            'original_name' => 'nota.pdf',
            'mime' => 'application/pdf',
            'size' => 500,
        ]);

        // Buat log BBM
        $fuelLog = $vehicle->fuelLogs()->create([
            'user_id' => $user->id,
            'filled_at' => Carbon::today()->toDateString(),
            'odometer' => 1300,
            'liters' => 20,
            'total_cost' => 200000,
        ]);

        // Buat jadwal
        $schedule = $vehicle->schedules()->create([
            'user_id' => $user->id,
            'title' => 'Servis Berkala',
            'trigger_type' => 'kilometer',
            'interval_km' => 5000,
            'next_due_km' => 6000,
        ]);

        // Pastikan file fisik ada sebelum penghapusan
        Storage::disk('local')->assertExists($pathVehicle);
        Storage::disk('local')->assertExists($pathNota);

        // 1. Tolak penghapusan jika kendaraan BELUM diarsipkan
        $responseNotArchived = $this->delete("/vehicles/{$vehicle->id}", [
            'confirm_plate' => 'B 7777 DEL',
        ]);
        $responseNotArchived->assertSessionHasErrors(['confirm_plate']);

        // Arsipkan dulu
        $vehicle->update(['archived_at' => now(), 'archive_reason' => 'rusak_berat']);

        // 2. Tolak jika konfirmasi nomor plat salah
        $responseWrongPlate = $this->delete("/vehicles/{$vehicle->id}", [
            'confirm_plate' => 'B 1234 SALAH',
        ]);
        $responseWrongPlate->assertSessionHasErrors(['confirm_plate']);

        // 3. Hapus permanen dengan plat yang benar
        $responseDelete = $this->delete("/vehicles/{$vehicle->id}", [
            'confirm_plate' => 'B 7777 DEL',
        ]);
        $responseDelete->assertRedirect();

        // Seluruh baris di DB harus hilang
        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
        $this->assertDatabaseMissing('maintenance_records', ['id' => $record->id]);
        $this->assertDatabaseMissing('fuel_logs', ['id' => $fuelLog->id]);
        $this->assertDatabaseMissing('maintenance_schedules', ['id' => $schedule->id]);
        $this->assertDatabaseMissing('attachments', ['id' => $attVehicle->id]);
        $this->assertDatabaseMissing('attachments', ['id' => $attRecord->id]);

        // Seluruh file fisik harus terhapus dari storage
        Storage::disk('local')->assertMissing($pathVehicle);
        Storage::disk('local')->assertMissing($pathNota);
    }

    public function test_home_asset_delete_cascades_all_relations_and_physical_attachment_files(): void
    {
        Storage::fake('local');

        $user = User::firstOrFail();
        $this->actingAs($user);
        $homeAsset = HomeAsset::create([
            'user_id' => $user->id,
            'name' => 'Pompa Air Sumur',
            'category' => 'air',
            'room' => 'Belakang',
            'condition' => 'normal',
        ]);

        $fileAsset = UploadedFile::fake()->image('pompa.jpg', 600, 600);
        $pathAsset = $fileAsset->store('attachments/'.$user->id, 'local');
        $attAsset = $homeAsset->attachments()->create([
            'user_id' => $user->id,
            'path' => $pathAsset,
            'original_name' => 'pompa.jpg',
            'mime' => 'image/jpeg',
            'size' => 1024,
        ]);

        $record = $homeAsset->maintenanceRecords()->create([
            'user_id' => $user->id,
            'done_at' => Carbon::today()->toDateString(),
            'service_type' => 'Ganti Kapasitor',
            'labor_cost' => 75000,
        ]);

        $fileNota = UploadedFile::fake()->create('nota_pompa.pdf', 400, 'application/pdf');
        $pathNota = $fileNota->store('attachments/'.$user->id, 'local');
        $attRecord = $record->attachments()->create([
            'user_id' => $user->id,
            'path' => $pathNota,
            'original_name' => 'nota_pompa.pdf',
            'mime' => 'application/pdf',
            'size' => 400,
        ]);

        Storage::disk('local')->assertExists($pathAsset);
        Storage::disk('local')->assertExists($pathNota);

        // Hapus aset rumah via controller
        $response = $this->delete("/home-assets/{$homeAsset->id}");
        $response->assertRedirect();

        $this->assertDatabaseMissing('home_assets', ['id' => $homeAsset->id]);
        $this->assertDatabaseMissing('maintenance_records', ['id' => $record->id]);
        $this->assertDatabaseMissing('attachments', ['id' => $attAsset->id]);
        $this->assertDatabaseMissing('attachments', ['id' => $attRecord->id]);

        Storage::disk('local')->assertMissing($pathAsset);
        Storage::disk('local')->assertMissing($pathNota);
    }
}
