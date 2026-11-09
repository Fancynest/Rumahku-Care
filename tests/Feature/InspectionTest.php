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
                ['label' => 'APAR (tekanan & masa berlaku tabung)', 'status' => 'ok', 'notes' => 'Jarum masih di zona hijau'],
                ['label' => 'Detektor Asap / Gas (uji sensor & baterai)', 'status' => 'perhatian', 'notes' => 'Baterai 9V mulai lemah'],
                ['label' => 'Panel MCB & Instalasi Kabel Utama', 'status' => 'bermasalah', 'notes' => 'Ada bau hangus di switch utama'],
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
        $this->assertEquals('Ditemukan Masalah', $run->status_label);

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
            'status' => 'bermasalah',
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

    public function test_convert_inspection_item_is_idempotent_and_does_not_create_duplicates(): void
    {
        $run = InspectionRun::create([
            'user_id' => $this->user->id,
            'inspectable_type' => HomeAsset::class,
            'inspectable_id' => $this->homeAsset->id,
            'title' => 'Inspeksi Gas',
            'inspected_at' => Carbon::today(),
        ]);

        $item = InspectionRunItem::create([
            'inspection_run_id' => $run->id,
            'item_label' => 'Selang Gas Retak',
            'status' => 'bermasalah',
        ]);

        // 1. Konversi pertama berhasil
        $this->actingAs($this->user)
            ->post(route('inspections.convert-damage', $item))
            ->assertRedirect()
            ->assertSessionHas('success');

        $item->refresh();
        $this->assertNotNull($item->damage_report_id);
        $this->assertDatabaseCount('damage_reports', 1);

        // 2. Konversi kedua tidak membuat duplikat
        $this->actingAs($this->user)
            ->post(route('inspections.convert-damage', $item))
            ->assertRedirect()
            ->assertSessionHas('info');

        $this->assertDatabaseCount('damage_reports', 1);

        // 3. Di view tersembunyi tombol konversi, diganti badge "Sudah Dikonversi"
        $viewResponse = $this->actingAs($this->user)->get(route('home-assets.show', $this->homeAsset->id));
        $viewResponse->assertSee('Sudah Dikonversi');
    }

    public function test_inspection_item_status_na_is_ignored_in_issues_count(): void
    {
        $run = InspectionRun::create([
            'user_id' => $this->user->id,
            'inspectable_type' => HomeAsset::class,
            'inspectable_id' => $this->homeAsset->id,
            'title' => 'Inspeksi Lengkap',
            'inspected_at' => Carbon::today(),
        ]);

        InspectionRunItem::create([
            'inspection_run_id' => $run->id,
            'item_label' => 'Item OK',
            'status' => 'ok',
        ]);
        InspectionRunItem::create([
            'inspection_run_id' => $run->id,
            'item_label' => 'Item NA',
            'status' => 'na',
        ]);
        InspectionRunItem::create([
            'inspection_run_id' => $run->id,
            'item_label' => 'Item Perhatian',
            'status' => 'perhatian',
        ]);

        $run->load('items');

        $this->assertTrue($run->has_issues);
        $this->assertFalse($run->has_danger);
        $this->assertEquals(1, $run->issues_count);
        $this->assertEquals('1 Perlu Perhatian', $run->status_label);
    }

    public function test_template_validation_rejects_invalid_key_or_mismatched_target_type(): void
    {
        // 1. Template key tidak ada di config
        $resInvalid = $this->actingAs($this->user)->post(route('inspections.store'), [
            'inspectable_type' => HomeAsset::class,
            'inspectable_id' => $this->homeAsset->id,
            'template_key' => 'tidak_ada_di_config',
            'title' => 'Inspeksi Salah',
            'inspected_at' => date('Y-m-d'),
            'items' => [
                ['label' => 'Item Bebas', 'status' => 'ok'],
            ],
        ]);
        $resInvalid->assertSessionHasErrors('template_key');

        // 2. Template key milik vehicle dipakai pada HomeAsset
        $resMismatch = $this->actingAs($this->user)->post(route('inspections.store'), [
            'inspectable_type' => HomeAsset::class,
            'inspectable_id' => $this->homeAsset->id,
            'template_key' => 'kendaraan_pra_jalan',
            'title' => 'Inspeksi Mismatch',
            'inspected_at' => date('Y-m-d'),
            'items' => [
                ['label' => 'Tekanan & Ketebalan Alur Ban', 'status' => 'ok'],
            ],
        ]);
        $resMismatch->assertSessionHasErrors('template_key');
    }

    public function test_template_validation_rejects_unmatched_label_unless_marked_as_custom(): void
    {
        // 1. Label tidak ada di template dan tidak ditandai custom -> gagal
        $resReject = $this->actingAs($this->user)->post(route('inspections.store'), [
            'inspectable_type' => HomeAsset::class,
            'inspectable_id' => $this->homeAsset->id,
            'template_key' => 'rumah_keselamatan',
            'title' => 'Inspeksi Custom Tanpa Tanda',
            'inspected_at' => date('Y-m-d'),
            'items' => [
                ['label' => 'Pipa Bocor Liar', 'status' => 'ok'],
            ],
        ]);
        $resReject->assertSessionHasErrors('items.0.label');

        // 2. Label tidak ada di template tapi eksplisit custom -> berhasil
        $resSuccess = $this->actingAs($this->user)->post(route('inspections.store'), [
            'inspectable_type' => HomeAsset::class,
            'inspectable_id' => $this->homeAsset->id,
            'template_key' => 'rumah_keselamatan',
            'title' => 'Inspeksi Custom Dengan Tanda',
            'inspected_at' => date('Y-m-d'),
            'items' => [
                ['label' => 'Pipa Bocor Liar', 'status' => 'ok', 'is_custom' => true],
            ],
        ]);
        $resSuccess->assertRedirect();
    }
}
