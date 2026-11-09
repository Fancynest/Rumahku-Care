<?php

namespace Tests\Feature;

use App\Models\DamageReport;
use App\Models\HomeAsset;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use App\Services\AttentionFeed;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DamageReportTest extends TestCase
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
            'brand' => 'Toyota',
            'model' => 'Avanza',
            'body_type' => 'mpv',
        ]);

        $this->vehicle = Vehicle::create([
            'user_id' => $this->user->id,
            'vehicle_model_id' => $vm->id,
            'plate' => 'B 5555 DMG',
            'year' => 2022,
            'color_key' => 'putih',
            'odometer' => 25000,
            'fuel_type' => 'bensin',
        ]);

        $this->homeAsset = HomeAsset::create([
            'user_id' => $this->user->id,
            'name' => 'Pompa Air Utama',
            'category' => 'air',
            'room' => 'Belakang',
            'condition' => 'normal',
        ]);
    }

    public function test_guest_cannot_manage_damage_reports(): void
    {
        $this->post(route('damage-reports.store'), [
            'reportable_type' => HomeAsset::class,
            'reportable_id' => $this->homeAsset->id,
            'title' => 'Pipa Bocor',
            'severity' => 'sedang',
            'reported_at' => date('Y-m-d'),
        ])->assertRedirect(route('login'));
    }

    public function test_user_can_create_damage_report_with_photo(): void
    {
        Storage::fake('local');

        $photo = UploadedFile::fake()->image('bocor.jpg', 600, 600);

        $response = $this->actingAs($this->user)->post(route('damage-reports.store'), [
            'reportable_type' => HomeAsset::class,
            'reportable_id' => $this->homeAsset->id,
            'title' => 'Pipa Hisap Bocor Halus',
            'description' => 'Air rembes terus menerus dari drat pipa.',
            'severity' => 'sedang',
            'reported_at' => Carbon::today()->format('Y-m-d'),
            'photo' => $photo,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('damage_reports', [
            'user_id' => $this->user->id,
            'reportable_type' => HomeAsset::class,
            'reportable_id' => $this->homeAsset->id,
            'title' => 'Pipa Hisap Bocor Halus',
            'severity' => 'sedang',
            'status' => 'dilaporkan',
        ]);

        $report = DamageReport::first();
        $this->assertNotNull($report);
        $this->assertCount(1, $report->attachments);
        Storage::disk('local')->assertExists($report->attachments->first()->path);
    }

    public function test_user_cannot_create_damage_report_for_foreign_asset(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->post(route('damage-reports.store'), [
            'reportable_type' => HomeAsset::class,
            'reportable_id' => $this->homeAsset->id,
            'title' => 'Kerusakan Ilegal',
            'severity' => 'ringan',
            'reported_at' => date('Y-m-d'),
        ]);

        $response->assertNotFound();
    }

    public function test_asset_condition_syncs_with_damage_severity_and_resets_on_resolve(): void
    {
        $this->assertEquals('normal', $this->homeAsset->condition);

        // 1. Lapor kerusakan sedang -> kondisi menjadi perlu_perhatian
        $this->actingAs($this->user)->post(route('damage-reports.store'), [
            'reportable_type' => HomeAsset::class,
            'reportable_id' => $this->homeAsset->id,
            'title' => 'Kran Rembes',
            'severity' => 'sedang',
            'reported_at' => date('Y-m-d'),
        ]);

        $this->homeAsset->refresh();
        $this->assertEquals('perlu_perhatian', $this->homeAsset->condition);

        // 2. Lapor kerusakan berat -> kondisi meningkat menjadi rusak
        $this->actingAs($this->user)->post(route('damage-reports.store'), [
            'reportable_type' => HomeAsset::class,
            'reportable_id' => $this->homeAsset->id,
            'title' => 'Dinamo Terbakar',
            'severity' => 'berat',
            'reported_at' => date('Y-m-d'),
        ]);

        $this->homeAsset->refresh();
        $this->assertEquals('rusak', $this->homeAsset->condition);

        // 3. Selesaikan kerusakan berat -> tersisa kerusakan sedang -> kondisi kembali ke perlu_perhatian
        $heavyReport = DamageReport::where('severity', 'berat')->first();
        $this->actingAs($this->user)->put(route('damage-reports.update-status', $heavyReport), [
            'status' => 'selesai',
            'resolved_at' => date('Y-m-d'),
            'repair_cost' => 450000,
        ]);

        $this->homeAsset->refresh();
        $this->assertEquals('perlu_perhatian', $this->homeAsset->condition);

        // 4. Selesaikan kerusakan sedang -> tidak ada kerusakan terbuka -> kondisi kembali ke normal
        $mediumReport = DamageReport::where('severity', 'sedang')->first();
        $this->actingAs($this->user)->put(route('damage-reports.update-status', $mediumReport), [
            'status' => 'selesai',
            'resolved_at' => date('Y-m-d'),
            'repair_cost' => 50000,
        ]);

        $this->homeAsset->refresh();
        $this->assertEquals('normal', $this->homeAsset->condition);
    }

    public function test_updating_status_to_resolved_requires_resolved_at_and_can_attach_receipt(): void
    {
        Storage::fake('local');

        $report = DamageReport::create([
            'user_id' => $this->user->id,
            'reportable_type' => Vehicle::class,
            'reportable_id' => $this->vehicle->id,
            'title' => 'Kampas Rem Depan Habis',
            'severity' => 'sedang',
            'status' => 'dilaporkan',
            'reported_at' => Carbon::today()->subDays(2),
        ]);

        // Gagal jika status selesai tanpa resolved_at
        $this->actingAs($this->user)->put(route('damage-reports.update-status', $report), [
            'status' => 'selesai',
            'resolved_at' => '',
        ])->assertSessionHasErrors(['resolved_at']);

        // Berhasil dengan resolved_at dan nota
        $receipt = UploadedFile::fake()->create('nota-bengkel.pdf', 150, 'application/pdf');

        $response = $this->actingAs($this->user)->put(route('damage-reports.update-status', $report), [
            'status' => 'selesai',
            'resolved_at' => Carbon::today()->format('Y-m-d'),
            'technician_name' => 'Pak Yanto Bengkel Sahabat',
            'repair_cost' => 175000,
            'notes' => 'Ganti kampas rem depan original baru.',
            'receipt' => $receipt,
        ]);

        $response->assertRedirect();

        $report->refresh();
        $this->assertEquals('selesai', $report->status);
        $this->assertEquals(175000, $report->repair_cost);
        $this->assertEquals('Pak Yanto Bengkel Sahabat', $report->technician_name);
        $this->assertCount(1, $report->attachments);
    }

    public function test_attention_feed_integrates_unresolved_heavy_and_medium_damage_reports(): void
    {
        // 1. Kerusakan berat -> mendesak di feed
        $heavyReport = DamageReport::create([
            'user_id' => $this->user->id,
            'reportable_type' => Vehicle::class,
            'reportable_id' => $this->vehicle->id,
            'title' => 'Radiator Pecah',
            'severity' => 'berat',
            'status' => 'dilaporkan',
            'reported_at' => Carbon::today(),
        ]);

        // 2. Kerusakan sedang -> perhatian di feed
        $mediumReport = DamageReport::create([
            'user_id' => $this->user->id,
            'reportable_type' => HomeAsset::class,
            'reportable_id' => $this->homeAsset->id,
            'title' => 'Stop Kontak Rusak',
            'severity' => 'sedang',
            'status' => 'dalam_perbaikan',
            'reported_at' => Carbon::today(),
        ]);

        // 3. Kerusakan ringan -> tidak masuk feed
        $lightReport = DamageReport::create([
            'user_id' => $this->user->id,
            'reportable_type' => Vehicle::class,
            'reportable_id' => $this->vehicle->id,
            'title' => 'Baret Spion Kanan',
            'severity' => 'ringan',
            'status' => 'dilaporkan',
            'reported_at' => Carbon::today(),
        ]);

        // 4. Kerusakan selesai -> tidak masuk feed
        $resolvedReport = DamageReport::create([
            'user_id' => $this->user->id,
            'reportable_type' => HomeAsset::class,
            'reportable_id' => $this->homeAsset->id,
            'title' => 'Lampu Putus',
            'severity' => 'berat',
            'status' => 'selesai',
            'reported_at' => Carbon::today()->subDays(3),
            'resolved_at' => Carbon::today(),
        ]);

        $feed = app(AttentionFeed::class);
        $feedItems = $feed->get($this->user);

        // Heavy report is present as mendesak
        $heavyItem = $feedItems->firstWhere('id', $heavyReport->id);
        $this->assertNotNull($heavyItem);
        $this->assertEquals('kerusakan', $heavyItem->type);
        $this->assertEquals('mendesak', $heavyItem->status);

        // Medium report is present as perhatian
        $mediumItem = $feedItems->firstWhere('id', $mediumReport->id);
        $this->assertNotNull($mediumItem);
        $this->assertEquals('perhatian', $mediumItem->status);

        // Light report is NOT in feed
        $this->assertNull($feedItems->firstWhere('id', $lightReport->id));

        // Resolved report is NOT in feed
        $this->assertNull($feedItems->firstWhere('id', $resolvedReport->id));
    }

    public function test_other_user_cannot_update_or_delete_damage_report(): void
    {
        $otherUser = User::factory()->create();

        $report = DamageReport::create([
            'user_id' => $this->user->id,
            'reportable_type' => Vehicle::class,
            'reportable_id' => $this->vehicle->id,
            'title' => 'Kerusakan Sensitif',
            'severity' => 'berat',
            'status' => 'dilaporkan',
            'reported_at' => Carbon::today(),
        ]);

        $this->actingAs($otherUser)
            ->put(route('damage-reports.update-status', $report), [
                'status' => 'selesai',
                'resolved_at' => date('Y-m-d'),
            ])
            ->assertNotFound();

        $this->actingAs($otherUser)
            ->delete(route('damage-reports.destroy', $report))
            ->assertNotFound();
    }

    public function test_attachment_limit_of_five_is_enforced_in_damage_report_status_update(): void
    {
        Storage::fake('local');

        $report = DamageReport::create([
            'user_id' => $this->user->id,
            'reportable_type' => Vehicle::class,
            'reportable_id' => $this->vehicle->id,
            'title' => 'Knalpot Bocor',
            'severity' => 'sedang',
            'status' => 'dilaporkan',
            'reported_at' => Carbon::today(),
        ]);

        // Buat 5 lampiran awal pada damage report
        for ($i = 1; $i <= 5; $i++) {
            $report->attachments()->create([
                'user_id' => $this->user->id,
                'path' => "attachments/{$this->user->id}/foto_{$i}.jpg",
                'original_name' => "foto_{$i}.jpg",
                'mime' => 'image/jpeg',
                'size' => 1024,
            ]);
        }

        $this->assertEquals(5, $report->attachments()->count());

        $newReceipt = UploadedFile::fake()->create('nota_ke_6.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->user)->put(route('damage-reports.update-status', $report), [
            'status' => 'selesai',
            'resolved_at' => Carbon::today()->format('Y-m-d'),
            'receipt' => $newReceipt,
        ]);

        $response->assertSessionHasErrors(['receipt']);
        $this->assertEquals(5, $report->attachments()->count());
    }
}
