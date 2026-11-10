<?php

namespace Tests\Feature;

use App\Models\FuelLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\FuelCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FuelLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->vehicle = Vehicle::create([
            'user_id' => $this->user->id,
            'plate' => 'B 1111 BBM',
            'odometer' => 10000,
            'color_key' => 'hitam',
            'fuel_type' => 'Bensin',
        ]);
    }

    public function test_user_can_store_fuel_log_with_comma_decimal_and_thousand_dots(): void
    {
        $this->actingAs($this->user);

        $response = $this->post("/vehicles/{$this->vehicle->id}/fuel-logs", [
            'filled_at' => Carbon::today()->toDateString(),
            'odometer' => '10.500',
            'liters' => '32,5',
            'total_cost' => '325.000',
            'fuel_grade' => 'Pertamax',
            'is_full_tank' => '1',
            'station' => 'SPBU Dago',
            'notes' => 'Isi full sebelum keluar kota',
        ]);

        $response->assertRedirect(route('vehicles.index', ['v' => $this->vehicle->id]).'#bbm');
        $this->assertDatabaseHas('fuel_logs', [
            'vehicle_id' => $this->vehicle->id,
            'user_id' => $this->user->id,
            'odometer' => 10500,
            'liters' => 32.50,
            'total_cost' => 325000,
            'fuel_grade' => 'Pertamax',
            'is_full_tank' => 1,
            'station' => 'SPBU Dago',
        ]);

        // Vehicle odometer must rise to 10500
        $this->assertSame(10500, $this->vehicle->fresh()->odometer);
    }

    public function test_cannot_store_fuel_log_in_the_future(): void
    {
        $this->actingAs($this->user);

        $response = $this->post("/vehicles/{$this->vehicle->id}/fuel-logs", [
            'filled_at' => Carbon::tomorrow()->toDateString(),
            'odometer' => 10500,
            'liters' => 30,
            'total_cost' => 300000,
        ]);

        $response->assertSessionHasErrors(['filled_at']);
    }

    public function test_cannot_store_fuel_log_conflicting_with_service_records_or_fuel_timeline(): void
    {
        $this->actingAs($this->user);

        // Service record at 2026-01-01 -> 5000 KM
        $this->vehicle->maintenanceRecords()->create([
            'user_id' => $this->user->id,
            'done_at' => '2026-01-01',
            'odometer' => 5000,
            'service_type' => 'Servis Berkala',
        ]);

        // Fuel log at 2026-06-01 -> 10000 KM
        $this->vehicle->fuelLogs()->create([
            'user_id' => $this->user->id,
            'filled_at' => '2026-06-01',
            'odometer' => 10000,
            'liters' => 40,
            'total_cost' => 400000,
            'is_full_tank' => true,
        ]);

        // 1. Try to record fuel at 2026-03-01 with 4000 KM (lower than 5000 KM on 2026-01-01) -> Fails
        $responseLow = $this->post("/vehicles/{$this->vehicle->id}/fuel-logs", [
            'filled_at' => '2026-03-01',
            'odometer' => 4000,
            'liters' => 20,
            'total_cost' => 200000,
        ]);
        $responseLow->assertSessionHasErrors(['odometer']);

        // 2. Try to record fuel at 2026-03-01 with 12000 KM (higher than 10000 KM on 2026-06-01) -> Fails
        $responseHigh = $this->post("/vehicles/{$this->vehicle->id}/fuel-logs", [
            'filled_at' => '2026-03-01',
            'odometer' => 12000,
            'liters' => 20,
            'total_cost' => 200000,
        ]);
        $responseHigh->assertSessionHasErrors(['odometer']);

        // 3. Valid backfill between: 2026-03-01 with 7500 KM -> Succeeds
        $responseValid = $this->post("/vehicles/{$this->vehicle->id}/fuel-logs", [
            'filled_at' => '2026-03-01',
            'odometer' => 7500,
            'liters' => 20,
            'total_cost' => 200000,
        ]);
        $responseValid->assertRedirect();
        $this->assertDatabaseHas('fuel_logs', [
            'vehicle_id' => $this->vehicle->id,
            'odometer' => 7500,
        ]);
    }

    public function test_full_tank_consumption_calculation_with_partial_and_multiple_intervals(): void
    {
        // 1. First full tank: baseline (no previous reading -> consumption is null)
        $this->vehicle->fuelLogs()->create([
            'user_id' => $this->user->id,
            'filled_at' => '2026-01-01',
            'odometer' => 10000,
            'liters' => 40,
            'total_cost' => 400000,
            'is_full_tank' => true,
        ]);

        // 2. Partial fill-up: 10 L
        $this->vehicle->fuelLogs()->create([
            'user_id' => $this->user->id,
            'filled_at' => '2026-01-10',
            'odometer' => 10200,
            'liters' => 10,
            'total_cost' => 100000,
            'is_full_tank' => false,
        ]);

        // 3. Second full tank: distance 600 KM (10600 - 10000), total liters 40 L (10 + 30) -> 15.00 km/L
        $this->vehicle->fuelLogs()->create([
            'user_id' => $this->user->id,
            'filled_at' => '2026-01-20',
            'odometer' => 10600,
            'liters' => 30,
            'total_cost' => 300000,
            'is_full_tank' => true,
        ]);

        // 4. Third full tank: distance 500 KM (11100 - 10600), liters 35 L -> 14.29 km/L
        $this->vehicle->fuelLogs()->create([
            'user_id' => $this->user->id,
            'filled_at' => '2026-01-30',
            'odometer' => 11100,
            'liters' => 35,
            'total_cost' => 350000,
            'is_full_tank' => true,
        ]);

        $calculator = app(FuelCalculator::class);
        $metrics = $calculator->calculate($this->vehicle);

        // Check latest interval: 14.29 km/L
        $this->assertEquals(14.29, $metrics['latest_km_l']);

        // Check average 5 intervals: (15.00 + 14.29) / 2 = 14.65 km/L (rounded)
        $this->assertEquals(14.65, $metrics['average_5_km_l']);

        // Cost per KM: total cost in intervals = (100k + 300k + 350k) = 750,000. Total dist = 600 + 500 = 1100 KM.
        // 750000 / 1100 = 682 Rp/km
        $this->assertEquals(682, $metrics['cost_per_km']);

        // Check first log consumption is null
        $firstLog = $metrics['logs']->firstWhere('odometer', 10000);
        $this->assertNull($firstLog->consumption_km_l);

        // Check second log (partial) consumption is null
        $partialLog = $metrics['logs']->firstWhere('odometer', 10200);
        $this->assertNull($partialLog->consumption_km_l);

        // Check third log (second full tank) consumption is 15.00
        $secondFullLog = $metrics['logs']->firstWhere('odometer', 10600);
        $this->assertEquals(15.00, $secondFullLog->consumption_km_l);
    }

    public function test_vehicle_odometer_does_not_drop_on_backfill_and_rises_on_higher_reading(): void
    {
        $this->actingAs($this->user);

        // Current vehicle odometer is 10000.
        // Set it higher to 20000
        $this->vehicle->update(['odometer' => 20000]);

        // Backfill a fuel log with 15000 KM
        $response = $this->post("/vehicles/{$this->vehicle->id}/fuel-logs", [
            'filled_at' => Carbon::yesterday()->toDateString(),
            'odometer' => 15000,
            'liters' => 25,
            'total_cost' => 250000,
        ]);

        $response->assertRedirect();
        // Vehicle odometer must remain 20000
        $this->assertSame(20000, $this->vehicle->fresh()->odometer);

        // Store a new fuel log with 22000 KM
        $responseNew = $this->post("/vehicles/{$this->vehicle->id}/fuel-logs", [
            'filled_at' => Carbon::today()->toDateString(),
            'odometer' => 22000,
            'liters' => 30,
            'total_cost' => 300000,
        ]);

        $responseNew->assertRedirect();
        // Vehicle odometer must increase to 22000
        $this->assertSame(22000, $this->vehicle->fresh()->odometer);
    }

    public function test_user_cannot_access_or_modify_or_delete_fuel_log_of_another_user(): void
    {
        $otherUser = User::factory()->create();
        $otherVehicle = Vehicle::create([
            'user_id' => $otherUser->id,
            'plate' => 'D 9999 OTH',
            'odometer' => 5000,
            'color_key' => 'putih',
            'fuel_type' => 'Bensin',
        ]);

        $otherFuelLog = $otherVehicle->fuelLogs()->create([
            'user_id' => $otherUser->id,
            'filled_at' => '2026-02-01',
            'odometer' => 5200,
            'liters' => 20,
            'total_cost' => 200000,
            'is_full_tank' => true,
        ]);

        $this->actingAs($this->user);

        // 1. Cannot store to other vehicle -> 404
        $storeResponse = $this->post("/vehicles/{$otherVehicle->id}/fuel-logs", [
            'filled_at' => Carbon::today()->toDateString(),
            'odometer' => 5500,
            'liters' => 20,
            'total_cost' => 200000,
        ]);
        $storeResponse->assertStatus(404);

        // 2. Cannot update other user's fuel log -> 404
        $updateResponse = $this->put("/fuel-logs/{$otherFuelLog->id}", [
            'filled_at' => '2026-02-01',
            'odometer' => 5300,
            'liters' => 25,
            'total_cost' => 250000,
        ]);
        $updateResponse->assertStatus(404);

        // 3. Cannot delete other user's fuel log -> 404
        $deleteResponse = $this->delete("/fuel-logs/{$otherFuelLog->id}");
        $deleteResponse->assertStatus(404);
    }

    public function test_user_can_update_and_delete_own_fuel_log(): void
    {
        $this->actingAs($this->user);

        $log = $this->vehicle->fuelLogs()->create([
            'user_id' => $this->user->id,
            'filled_at' => Carbon::yesterday()->toDateString(),
            'odometer' => 10200,
            'liters' => 25,
            'total_cost' => 250000,
            'is_full_tank' => true,
            'fuel_grade' => 'Pertalite',
        ]);

        // Update
        $updateResponse = $this->put("/fuel-logs/{$log->id}", [
            'filled_at' => Carbon::yesterday()->toDateString(),
            'odometer' => 10250,
            'liters' => '26,5',
            'total_cost' => '265.000',
            'fuel_grade' => 'Pertamax',
            'is_full_tank' => '1',
            'notes' => 'Diperbarui',
        ]);

        $updateResponse->assertRedirect(route('vehicles.index', ['v' => $this->vehicle->id]).'#bbm');
        $this->assertDatabaseHas('fuel_logs', [
            'id' => $log->id,
            'odometer' => 10250,
            'liters' => 26.50,
            'total_cost' => 265000,
            'fuel_grade' => 'Pertamax',
            'notes' => 'Diperbarui',
        ]);

        // Delete
        $deleteResponse = $this->delete("/fuel-logs/{$log->id}");
        $deleteResponse->assertRedirect(route('vehicles.index', ['v' => $this->vehicle->id]).'#bbm');
        $this->assertDatabaseMissing('fuel_logs', ['id' => $log->id]);
    }

    public function test_user_can_upload_receipt_attachment_with_fuel_log(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('nota_bbm.pdf', 300, 'application/pdf');

        $response = $this->post("/vehicles/{$this->vehicle->id}/fuel-logs", [
            'filled_at' => Carbon::today()->toDateString(),
            'odometer' => 10800,
            'liters' => 30,
            'total_cost' => 300000,
            'file' => $file,
        ]);

        $response->assertRedirect();
        $fuelLog = FuelLog::where('vehicle_id', $this->vehicle->id)->where('odometer', 10800)->firstOrFail();

        $this->assertCount(1, $fuelLog->attachments);
        $attachment = $fuelLog->attachments->first();
        $this->assertSame('nota_bbm.pdf', $attachment->original_name);
        Storage::disk('local')->assertExists($attachment->path);
    }

    public function test_submitting_without_is_full_tank_field_stores_false_and_omits_kml_calculation(): void
    {
        $this->actingAs($this->user);

        // 1. Log pertama tangki penuh (baseline): value=1
        $this->post("/vehicles/{$this->vehicle->id}/fuel-logs", [
            'filled_at' => '2026-01-01',
            'odometer' => 10000,
            'liters' => 30,
            'total_cost' => 300000,
            'is_full_tank' => 1,
        ])->assertRedirect();

        $log1 = FuelLog::where('vehicle_id', $this->vehicle->id)->where('odometer', 10000)->firstOrFail();
        $this->assertTrue($log1->is_full_tank);

        // 2. Log kedua TANPA field is_full_tank sama sekali (simulasi browser checkbox tidak dicentang)
        $this->post("/vehicles/{$this->vehicle->id}/fuel-logs", [
            'filled_at' => '2026-01-10',
            'odometer' => 10200,
            'liters' => 10,
            'total_cost' => 100000,
            // 'is_full_tank' sengaja TIDAK dikirim
        ])->assertRedirect();

        $log2 = FuelLog::where('vehicle_id', $this->vehicle->id)->where('odometer', 10200)->firstOrFail();
        $this->assertFalse($log2->is_full_tank);

        // 3. Log ketiga tangki penuh: value=1
        $this->post("/vehicles/{$this->vehicle->id}/fuel-logs", [
            'filled_at' => '2026-01-20',
            'odometer' => 10600,
            'liters' => 30,
            'total_cost' => 300000,
            'is_full_tank' => '1',
        ])->assertRedirect();

        $log3 = FuelLog::where('vehicle_id', $this->vehicle->id)->where('odometer', 10600)->firstOrFail();
        $this->assertTrue($log3->is_full_tank);

        // Hitung metrik konsumsi: interval log kedua (parsial) TIDAK dihitung sendiri
        $calculator = app(FuelCalculator::class);
        $metrics = $calculator->calculate($this->vehicle);

        $calcLog1 = $metrics['logs']->firstWhere('id', $log1->id);
        $calcLog2 = $metrics['logs']->firstWhere('id', $log2->id);
        $calcLog3 = $metrics['logs']->firstWhere('id', $log3->id);

        $this->assertNull($calcLog1->consumption_km_l);
        $this->assertNull($calcLog2->consumption_km_l, 'Pengisian parsial tidak boleh menghasilkan km/L sendiri');
        $this->assertEquals(15.00, $calcLog3->consumption_km_l, 'Interval gabungan 600 km / 40 L harus 15.00 km/L');

        // Uji update TANPA field is_full_tank -> berubah menjadi false
        $this->put("/fuel-logs/{$log3->id}", [
            'filled_at' => '2026-01-20',
            'odometer' => 10600,
            'liters' => 30,
            'total_cost' => 300000,
            // 'is_full_tank' sengaja TIDAK dikirim
        ])->assertRedirect();

        $this->assertFalse($log3->fresh()->is_full_tank);

        // Uji update DENGAN value=1 -> kembali menjadi true
        $this->put("/fuel-logs/{$log3->id}", [
            'filled_at' => '2026-01-20',
            'odometer' => 10600,
            'liters' => 30,
            'total_cost' => 300000,
            'is_full_tank' => 1,
        ])->assertRedirect();

        $this->assertTrue($log3->fresh()->is_full_tank);
    }

    public function test_normalize_input_handles_various_formats_for_cost_odometer_and_liters(): void
    {
        $this->actingAs($this->user);

        // 1. Format: "Rp 52.000,-" untuk total_cost, "10.600,00" untuk odometer, "12,5" untuk liters
        $response1 = $this->post("/vehicles/{$this->vehicle->id}/fuel-logs", [
            'filled_at' => Carbon::today()->toDateString(),
            'odometer' => '10.600,00',
            'liters' => '12,5',
            'total_cost' => 'Rp 52.000,-',
            'is_full_tank' => 1,
        ]);
        $response1->assertRedirect();

        $this->assertDatabaseHas('fuel_logs', [
            'vehicle_id' => $this->vehicle->id,
            'odometer' => 10600,
            'liters' => 12.50,
            'total_cost' => 52000,
        ]);

        // 2. Format: "52.000,00" untuk total_cost, "10.800,-" untuk odometer, "14.25 L" untuk liters
        $response2 = $this->post("/vehicles/{$this->vehicle->id}/fuel-logs", [
            'filled_at' => Carbon::today()->toDateString(),
            'odometer' => '10.800,-',
            'liters' => '14.25 L',
            'total_cost' => '52.000,00',
            'is_full_tank' => 1,
        ]);
        $response2->assertRedirect();

        $this->assertDatabaseHas('fuel_logs', [
            'vehicle_id' => $this->vehicle->id,
            'odometer' => 10800,
            'liters' => 14.25,
            'total_cost' => 52000,
        ]);

        // 3. Format desimal lain pada total_cost: "52.000,50" -> 52000, "10,-" liter -> 10.00
        $response3 = $this->post("/vehicles/{$this->vehicle->id}/fuel-logs", [
            'filled_at' => Carbon::today()->toDateString(),
            'odometer' => '11.000',
            'liters' => '10,-',
            'total_cost' => '52.000,50',
            'is_full_tank' => 1,
        ]);
        $response3->assertRedirect();

        $this->assertDatabaseHas('fuel_logs', [
            'vehicle_id' => $this->vehicle->id,
            'odometer' => 11000,
            'liters' => 10.00,
            'total_cost' => 52000,
        ]);
    }
}
