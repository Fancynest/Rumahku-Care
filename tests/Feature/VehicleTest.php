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
}
