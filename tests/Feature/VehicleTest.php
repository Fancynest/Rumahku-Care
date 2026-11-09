<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_cannot_store_service_log_with_odometer_lower_than_current(): void
    {
        $vehicle = Vehicle::firstOrFail();

        $response = $this->post("/vehicles/{$vehicle->id}/service-logs", [
            'done_at' => '2026-11-09',
            'odometer' => $vehicle->odometer - 500,
        ]);

        $response->assertSessionHasErrors(['odometer']);
    }

    public function test_cannot_store_service_log_with_invalid_data(): void
    {
        $vehicle = Vehicle::firstOrFail();

        $response = $this->post("/vehicles/{$vehicle->id}/service-logs", []);

        $response->assertSessionHasErrors(['done_at', 'odometer']);
    }
}
