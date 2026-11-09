<?php

namespace Tests\Feature;

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
    }

    public function test_can_view_vehicles_list_with_seeded_data(): void
    {
        $response = $this->get('/vehicles');

        $response->assertStatus(200);
        $response->assertSee('Kendaraan Saya');
        $response->assertSee('B 2341 KLS');
        $response->assertSee('B 1234 GAB');
        $response->assertSee('B 9999 KRA');
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

    public function test_vehicle_service_status_based_on_service_logs(): void
    {
        $vehicle = Vehicle::create([
            'plate' => 'B 3333 TST',
            'odometer' => 10000,
            'service_interval_km' => 5000,
            'color_key' => 'hitam',
            'fuel_type' => 'Bensin',
        ]);

        // Belum ada log
        $this->assertSame('Belum ada catatan servis', $vehicle->status_label);

        // Servis terakhir di 5300 KM, sekarang 10000 KM (selisih 4700 < 4800) -> Kondisi Prima
        $vehicle->serviceLogs()->create([
            'serviced_at' => '2026-08-01',
            'odometer' => 5300,
            'notes' => 'Servis berkala',
        ]);
        $vehicle->refresh();
        $this->assertSame('Kondisi Prima', $vehicle->status_label);

        // Odometer naik jadi 10100 KM (selisih 4800 >= 4800) -> Waktunya Servis
        $vehicle->update(['odometer' => 10100]);
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
        $this->assertDatabaseHas('service_logs', [
            'vehicle_id' => $vehicle->id,
            'serviced_at' => '2026-11-09 00:00:00',
            'odometer' => $vehicle->odometer + 100,
            'notes' => 'Ganti oli transmisi dan filter',
        ]);

        $this->assertEquals($vehicle->odometer + 100, $vehicle->fresh()->odometer);
    }

    public function test_cannot_store_service_log_with_invalid_data(): void
    {
        $vehicle = Vehicle::firstOrFail();

        $response = $this->post("/vehicles/{$vehicle->id}/service-logs", []);

        $response->assertSessionHasErrors(['serviced_at', 'odometer']);
    }
}
