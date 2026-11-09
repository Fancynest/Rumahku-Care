<?php

namespace Tests\Feature;

use App\Models\HomeAsset;
use App\Models\InspectionRun;
use App\Models\InspectionRunItem;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InspectionTest extends TestCase
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
            'brand' => 'Yamaha',
            'model' => 'NMAX 155',
            'body_type' => 'motor_matic',
        ]);

        $this->vehicle = Vehicle::create([
            'user_id' => $this->user->id,
            'vehicle_model_id' => $vm->id,
            'plate' => 'B 7777 INSP',
            'year' => 2023,
            'color_key' => 'hitam',
            'odometer' => 12000,
            'fuel_type' => 'bensin',
        ]);

        $this->homeAsset = HomeAsset::create([
            'user_id' => $this->user->id,
            'name' => 'Instalasi Listrik & APAR',
            'category' => 'keamanan',
            'room' => 'Area Depan',
            'condition' => 'normal',
            'last_inspected_at' => null,
        ]);
    }

    public function test_guest_cannot_submit_inspections(): void
    {
        $this->post(route('inspections.store'), [
            'inspectable_type' => HomeAsset::class,
            'inspectable_id' => $this->homeAsset->id,
            'title' => 'Inspeksi Rutin',
            'inspected_at' => date('Y-m-d'),
            'items' => [
                ['label' => 'APAR', 'status' => 'ok'],
            ],
        ])->assertRedirect(route('login'));
    }

    public function test_user_can_submit_inspection_and_updates_last_inspected_at(): void
    {
        $this->assertNull($this->homeAsset->last_inspected_at);

        $inspectDate = Carbon::today()->format('Y-m-d');

        $response = $this->actingAs($this->user)->post(route('inspections.store'), [
            'inspectable_type' => HomeAsset::class,
            'inspectable_id' => $this->homeAsset->id,
            'template_key' => 'rumah_keselamatan',
            'title' => 'Inspeksi Keselamatan Semester 1',
            'inspected_at' => $inspectDate,
            'notes' => 'Pemeriksaan rutin berkala seluruh titik keselamatan.',
            'items' => [
                ['label' => 'APAR (tekanan tabung)', 'status' => 'ok', 'notes' => 'Jarum masih di zona hijau'],
                ['label' => 'Detektor Asap', 'status' => 'perhatian', 'notes' => 'Baterai 9V mulai lemah'],
                ['label' => 'Panel MCB', 'status' => 'bahaya', 'notes' => 'Ada bau hangus di switch utama'],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('inspection_runs', [
            'user_id' => $this->user->id,
            'inspectable_type' => HomeAsset::class,
            'inspectable_id' => $this->homeAsset->id,
            'template_key' => 'rumah_keselamatan',
            'title' => 'Inspeksi Keselamatan Semester 1',
        ]);

        $run = InspectionRun::first();
        $this->assertNotNull($run);
        $this->assertCount(3, $run->items);
        $this->assertTrue($run->has_issues);
        $this->assertTrue($run->has_danger);
        $this->assertEquals(2, $run->issues_count);
        $this->assertEquals('Ditemukan Bahaya', $run->status_label);

        // Memastikan last_inspected_at pada home_assets terupdate
        $this->homeAsset->refresh();
        $this->assertEquals($inspectDate, $this->homeAsset->last_inspected_at?->format('Y-m-d'));
    }

    public function test_user_cannot_submit_inspection_for_foreign_asset(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->post(route('inspections.store'), [
            'inspectable_type' => HomeAsset::class,
            'inspectable_id' => $this->homeAsset->id,
            'title' => 'Inspeksi Ilegal',
            'inspected_at' => date('Y-m-d'),
            'items' => [
                ['label' => 'APAR', 'status' => 'ok'],
            ],
        ]);

        $response->assertNotFound();
    }

    public function test_convert_danger_inspection_item_creates_heavy_damage_report_and_sets_asset_broken(): void
    {
        $run = InspectionRun::create([
            'user_id' => $this->user->id,
            'inspectable_type' => HomeAsset::class,
            'inspectable_id' => $this->homeAsset->id,
            'title' => 'Inspeksi Panel',
            'inspected_at' => Carbon::today(),
        ]);

        $itemDanger = InspectionRunItem::create([
            'inspection_run_id' => $run->id,
            'item_label' => 'Kabel MCB Meleleh',
            'status' => 'bahaya',
            'notes' => 'Isolasi kabel terkelupas dan panas berlebih.',
        ]);

        $this->assertEquals('normal', $this->homeAsset->condition);

        $response = $this->actingAs($this->user)->post(route('inspections.convert-damage', $itemDanger));
        $response->assertRedirect();

        $this->assertDatabaseHas('damage_reports', [
            'user_id' => $this->user->id,
            'reportable_type' => HomeAsset::class,
            'reportable_id' => $this->homeAsset->id,
            'title' => 'Temuan: Kabel MCB Meleleh',
            'severity' => 'berat',
            'status' => 'dilaporkan',
        ]);

        $this->homeAsset->refresh();
        $this->assertEquals('rusak', $this->homeAsset->condition);
    }

    public function test_convert_warning_inspection_item_creates_medium_damage_report(): void
    {
        $run = InspectionRun::create([
            'user_id' => $this->user->id,
            'inspectable_type' => Vehicle::class,
            'inspectable_id' => $this->vehicle->id,
            'title' => 'Inspeksi Ban Motor',
            'inspected_at' => Carbon::today(),
        ]);

        $itemWarning = InspectionRunItem::create([
            'inspection_run_id' => $run->id,
            'item_label' => 'Tekanan Ban Belakang Rendah',
            'status' => 'perhatian',
            'notes' => 'Tekanan cuma 20 psi, perlu cek bocor halus.',
        ]);

        $response = $this->actingAs($this->user)->post(route('inspections.convert-damage', $itemWarning));
        $response->assertRedirect();

        $this->assertDatabaseHas('damage_reports', [
            'user_id' => $this->user->id,
            'reportable_type' => Vehicle::class,
            'reportable_id' => $this->vehicle->id,
            'title' => 'Temuan: Tekanan Ban Belakang Rendah',
            'severity' => 'sedang',
            'status' => 'dilaporkan',
        ]);
    }

    public function test_other_user_cannot_convert_foreign_inspection_item(): void
    {
        $otherUser = User::factory()->create();

        $run = InspectionRun::create([
            'user_id' => $this->user->id,
            'inspectable_type' => Vehicle::class,
            'inspectable_id' => $this->vehicle->id,
            'title' => 'Inspeksi Rahasia',
            'inspected_at' => Carbon::today(),
        ]);

        $item = InspectionRunItem::create([
            'inspection_run_id' => $run->id,
            'item_label' => 'Rantai Kering',
            'status' => 'perhatian',
        ]);

        $this->actingAs($otherUser)
            ->post(route('inspections.convert-damage', $item))
            ->assertNotFound();
    }
}
