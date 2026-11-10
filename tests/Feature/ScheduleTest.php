<?php

namespace Tests\Feature;

use App\Models\HomeAsset;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTest extends TestCase
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

    public function test_user_can_complete_km_schedule_calculates_next_due_and_updates_odometer(): void
    {
        $user = User::firstOrFail();
        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'plate' => 'B 5555 KM',
            'odometer' => 10000,
            'color_key' => 'hitam',
            'fuel_type' => 'Bensin',
        ]);

        $schedule = $vehicle->schedules()->create([
            'user_id' => $user->id,
            'title' => 'Ganti oli mesin',
            'trigger_type' => 'kilometer',
            'interval_km' => 5000,
            'next_due_km' => 15000,
            'priority' => 'mendesak',
            'is_active' => true,
        ]);

        $response = $this->post("/schedules/{$schedule->id}/complete", [
            'done_at' => '2026-11-09',
            'odometer' => 10250,
            'labor_cost' => 50000,
            'parts_cost' => 120000,
            'parts' => 'Oli Shell Advance 10W-40',
            'notes' => 'Servis beres di bengkel langganan',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('maintenance_records', [
            'schedule_id' => $schedule->id,
            'odometer' => 10250,
            'labor_cost' => 50000,
            'parts_cost' => 120000,
        ]);

        $vehicle->refresh();
        $this->assertSame(10250, $vehicle->odometer);

        $schedule->refresh();
        $this->assertSame(10250, $schedule->last_done_km);
        $this->assertSame(15250, $schedule->next_due_km); // 10250 + 5000
    }

    public function test_user_can_complete_date_schedule_calculates_next_due_date(): void
    {
        $user = User::firstOrFail();
        $asset = HomeAsset::firstOrFail();

        $schedule = $asset->schedules()->create([
            'user_id' => $user->id,
            'title' => 'Cuci filter AC rutin',
            'trigger_type' => 'interval_hari',
            'interval_days' => 90,
            'next_due_date' => '2026-11-09',
            'priority' => 'perhatian',
            'is_active' => true,
        ]);

        $response = $this->post("/schedules/{$schedule->id}/complete", [
            'done_at' => '2026-11-09',
            'labor_cost' => 75000,
            'notes' => 'Filter bersih',
        ]);

        $response->assertRedirect();

        $schedule->refresh();
        $expectedNextDue = Carbon::parse('2026-11-09')->addDays(90)->toDateString();
        $this->assertSame($expectedNextDue, $schedule->next_due_date->toDateString());
    }

    public function test_cannot_complete_km_schedule_with_decreased_odometer(): void
    {
        $user = User::firstOrFail();
        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'plate' => 'B 6666 KM',
            'odometer' => 10000,
            'color_key' => 'hitam',
            'fuel_type' => 'Bensin',
        ]);

        $vehicle->maintenanceRecords()->create([
            'user_id' => $user->id,
            'done_at' => Carbon::yesterday()->toDateString(),
            'odometer' => 10000,
            'service_type' => 'Catatan Kemarin',
        ]);

        $schedule = $vehicle->schedules()->create([
            'user_id' => $user->id,
            'title' => 'Ganti oli mesin',
            'trigger_type' => 'kilometer',
            'interval_km' => 5000,
            'next_due_km' => 15000,
            'priority' => 'mendesak',
            'is_active' => true,
        ]);

        $response = $this->post("/schedules/{$schedule->id}/complete", [
            'done_at' => Carbon::today()->toDateString(),
            'odometer' => 9500, // lower than previous record (10000)
        ]);

        $response->assertSessionHasErrors(['odometer']);
    }

    public function test_can_complete_schedule_as_backfill_without_reducing_vehicle_odometer(): void
    {
        $user = User::firstOrFail();
        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'plate' => 'B 8888 BKF',
            'odometer' => 20000,
            'color_key' => 'hitam',
            'fuel_type' => 'Bensin',
        ]);

        $schedule = $vehicle->schedules()->create([
            'user_id' => $user->id,
            'title' => 'Ganti Oli Sejarah',
            'trigger_type' => 'kilometer',
            'interval_km' => 5000,
            'next_due_km' => 25000,
            'priority' => 'normal',
            'is_active' => true,
        ]);

        $response = $this->post("/schedules/{$schedule->id}/complete", [
            'done_at' => Carbon::yesterday()->toDateString(),
            'odometer' => 15000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('maintenance_records', [
            'schedule_id' => $schedule->id,
            'odometer' => 15000,
        ]);

        // Vehicle odometer must remain 20000
        $this->assertSame(20000, $vehicle->fresh()->odometer);
    }

    public function test_user_a_cannot_complete_user_b_schedule(): void
    {
        $userA = User::firstOrFail();
        $userB = User::factory()->create(['email' => 'userb_sched@example.com']);

        $vehicleB = Vehicle::create([
            'user_id' => $userB->id,
            'plate' => 'D 9999 SBD',
            'odometer' => 2000,
            'color_key' => 'putih',
            'fuel_type' => 'Bensin',
        ]);

        $scheduleB = $vehicleB->schedules()->create([
            'user_id' => $userB->id,
            'title' => 'Servis Berkala B',
            'trigger_type' => 'kilometer',
            'interval_km' => 5000,
            'next_due_km' => 7000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($userA)->post("/schedules/{$scheduleB->id}/complete", [
            'done_at' => '2026-11-09',
            'odometer' => 3000,
        ]);

        $response->assertStatus(404);
    }

    public function test_user_can_view_schedules_index_with_filters(): void
    {
        $responseAll = $this->get('/schedules?filter=all');
        $responseAll->assertStatus(200);
        $responseAll->assertSee('Jadwal Perawatan');
        $responseAll->assertSee('Mulai dari Template');

        $responseVehicle = $this->get('/schedules?filter=vehicle');
        $responseVehicle->assertStatus(200);

        $responseAsset = $this->get('/schedules?filter=home_asset');
        $responseAsset->assertStatus(200);
    }

    public function test_user_can_create_schedule_for_home_asset(): void
    {
        $user = User::firstOrFail();
        $asset = HomeAsset::firstOrFail();

        $response = $this->post('/schedules', [
            'subject_type' => HomeAsset::class,
            'subject_id' => $asset->id,
            'title' => 'Cek & Bersihkan Talang',
            'trigger_type' => 'interval_hari',
            'interval_days' => 120,
            'first_due_date' => '2026-11-15',
            'priority' => 'normal',
            'notes' => 'Bersihkan kotoran menjelang musim hujan',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('maintenance_schedules', [
            'user_id' => $user->id,
            'subject_type' => HomeAsset::class,
            'subject_id' => $asset->id,
            'title' => 'Cek & Bersihkan Talang',
            'interval_days' => 120,
        ]);
    }

    public function test_user_can_delete_schedule(): void
    {
        $user = User::firstOrFail();
        $vehicle = Vehicle::firstOrFail();

        $schedule = $vehicle->schedules()->create([
            'user_id' => $user->id,
            'title' => 'Jadwal yang akan dihapus',
            'trigger_type' => 'kilometer',
            'interval_km' => 5000,
            'priority' => 'normal',
            'is_active' => true,
        ]);

        $response = $this->delete("/schedules/{$schedule->id}");
        $response->assertRedirect();

        $this->assertDatabaseMissing('maintenance_schedules', [
            'id' => $schedule->id,
        ]);
    }

    public function test_overdue_date_schedule_marked_complete_is_deactivated_and_removed_from_overdue_list(): void
    {
        $user = User::firstOrFail();
        $asset = HomeAsset::firstOrFail();

        $schedule = $asset->schedules()->create([
            'user_id' => $user->id,
            'title' => 'Penggantian Filter Khusus',
            'trigger_type' => 'tanggal',
            'next_due_date' => Carbon::yesterday()->toDateString(),
            'priority' => 'mendesak',
            'is_active' => true,
        ]);

        $this->assertSame('terlambat', $schedule->status);

        // Before completion, schedule is visible in overdue list
        $responseBefore = $this->get('/schedules?filter=all');
        $responseBefore->assertSee('Penggantian Filter Khusus');
        $responseBefore->assertSee('Terlambat');

        // Complete the schedule
        $responseComplete = $this->post("/schedules/{$schedule->id}/complete", [
            'done_at' => Carbon::today()->toDateString(),
            'notes' => 'Selesai diganti',
        ]);

        $responseComplete->assertRedirect();

        $schedule->refresh();
        $this->assertFalse((bool) $schedule->is_active);
        $this->assertSame(Carbon::today()->toDateString(), $schedule->last_done_at?->format('Y-m-d'));

        // Clear session flash so success message doesn't match the schedule title
        $this->flushSession();

        // After completion, schedule is inactive and disappears from active schedules / overdue list
        $responseAfter = $this->get('/schedules?filter=all');
        $responseAfter->assertDontSee('Penggantian Filter Khusus');
    }

    public function test_user_can_update_own_schedule(): void
    {
        $user = User::firstOrFail();
        $asset = HomeAsset::firstOrFail();

        $schedule = $asset->schedules()->create([
            'user_id' => $user->id,
            'title' => 'Judul Lama',
            'trigger_type' => 'interval_hari',
            'interval_days' => 30,
            'priority' => 'normal',
            'is_active' => true,
        ]);

        $response = $this->put("/schedules/{$schedule->id}", [
            'title' => 'Judul Baru Diperbarui',
            'trigger_type' => 'interval_hari',
            'interval_days' => 60,
            'next_due_date' => '2026-12-01',
            'priority' => 'mendesak',
            'is_active' => 1,
            'notes' => 'Catatan baru hasil edit',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('maintenance_schedules', [
            'id' => $schedule->id,
            'title' => 'Judul Baru Diperbarui',
            'interval_days' => 60,
            'priority' => 'mendesak',
            'is_active' => 1,
            'notes' => 'Catatan baru hasil edit',
        ]);
    }

    public function test_user_cannot_update_other_users_schedule_returns_404(): void
    {
        $userA = User::firstOrFail();
        $userB = User::factory()->create(['email' => 'other_user@example.com']);

        $vehicleB = Vehicle::create([
            'user_id' => $userB->id,
            'plate' => 'B 9999 OTH',
            'odometer' => 5000,
            'color_key' => 'hitam',
            'fuel_type' => 'Bensin',
        ]);

        $scheduleB = $vehicleB->schedules()->create([
            'user_id' => $userB->id,
            'title' => 'Jadwal Milik User B',
            'trigger_type' => 'kilometer',
            'interval_km' => 5000,
            'priority' => 'normal',
            'is_active' => true,
        ]);

        // Acting as userA, updating scheduleB must return 404
        $response = $this->actingAs($userA)->put("/schedules/{$scheduleB->id}", [
            'title' => 'Coba Retas Judul',
            'trigger_type' => 'kilometer',
            'interval_km' => 5000,
            'priority' => 'normal',
            'is_active' => 1,
        ]);

        $response->assertStatus(404);
    }

    public function test_updating_home_asset_schedule_with_km_trigger_fails_validation(): void
    {
        $user = User::firstOrFail();
        $asset = HomeAsset::firstOrFail();

        $schedule = $asset->schedules()->create([
            'user_id' => $user->id,
            'title' => 'Jadwal Aset Rumah',
            'trigger_type' => 'interval_hari',
            'interval_days' => 30,
            'priority' => 'normal',
            'is_active' => true,
        ]);

        $response = $this->put("/schedules/{$schedule->id}", [
            'title' => 'Jadwal Aset Rumah Diubah ke KM',
            'trigger_type' => 'kilometer',
            'interval_km' => 5000,
            'priority' => 'normal',
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors(['trigger_type']);
    }

    public function test_schedules_index_orders_by_priority_within_same_status_group(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'plate' => 'B 9999 SRT',
            'odometer' => 5000,
            'color_key' => 'putih',
            'fuel_type' => 'Bensin',
        ]);

        // Create two schedules in the same status group (e.g. mendesak, 3 days due)
        $vehicle->schedules()->create([
            'user_id' => $user->id,
            'title' => 'Jadwal Normal Mendesak',
            'trigger_type' => 'interval_hari',
            'interval_days' => 30,
            'next_due_date' => Carbon::today()->addDays(3)->toDateString(),
            'priority' => 'normal',
            'is_active' => true,
        ]);

        $vehicle->schedules()->create([
            'user_id' => $user->id,
            'title' => 'Jadwal Darurat Mendesak',
            'trigger_type' => 'interval_hari',
            'interval_days' => 30,
            'next_due_date' => Carbon::today()->addDays(3)->toDateString(),
            'priority' => 'darurat',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/schedules');
        $response->assertStatus(200);

        $content = $response->getContent();
        $posDarurat = strpos($content, 'Jadwal Darurat Mendesak');
        $posNormal = strpos($content, 'Jadwal Normal Mendesak');

        $this->assertNotFalse($posDarurat);
        $this->assertNotFalse($posNormal);
        $this->assertTrue($posDarurat < $posNormal, 'Darurat schedule should appear before normal within same group');
    }

    public function test_archived_vehicle_cannot_be_subject_for_new_schedule_and_not_in_form_options(): void
    {
        $user = User::firstOrFail();
        $this->actingAs($user);

        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'plate' => 'B 7777 ARS',
            'odometer' => 5000,
            'color_key' => 'hitam',
            'fuel_type' => 'Bensin',
            'archived_at' => now(),
            'archive_reason' => 'dijual',
        ]);

        // Form jadwal tidak menyertakan kendaraan arsip sebagai pilihan subjek
        $responseIndex = $this->get('/schedules');
        $responseIndex->assertOk();
        $vehiclesInView = $responseIndex->viewData('vehicles');
        $this->assertFalse($vehiclesInView->contains('id', $vehicle->id));

        // Mencoba membuat jadwal untuk kendaraan arsip harus gagal 404
        $responseStore = $this->post('/schedules', [
            'subject_type' => Vehicle::class,
            'subject_id' => $vehicle->id,
            'title' => 'Ganti Oli Kendaraan Arsip',
            'trigger_type' => 'kilometer',
            'interval_km' => 5000,
            'priority' => 'normal',
        ]);

        $responseStore->assertStatus(404);
        $this->assertDatabaseMissing('maintenance_schedules', [
            'title' => 'Ganti Oli Kendaraan Arsip',
        ]);
    }
}
