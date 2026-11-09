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
}
