<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\HomeAsset;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use App\Services\AttentionFeed;
use App\Services\ScheduleStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
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
            'brand' => 'Honda',
            'model' => 'Vario 160',
            'body_type' => 'motor_matic',
        ]);

        $this->vehicle = Vehicle::create([
            'user_id' => $this->user->id,
            'vehicle_model_id' => $vm->id,
            'plate' => 'B 1234 DOC',
            'year' => 2023,
            'color_key' => 'hitam',
            'odometer' => 10000,
            'fuel_type' => 'bensin',
        ]);

        $this->homeAsset = HomeAsset::create([
            'user_id' => $this->user->id,
            'name' => 'AC Ruang Tamu',
            'category' => 'pendingin',
            'room' => 'Ruang Tamu',
            'condition' => 'normal',
        ]);
    }

    public function test_guest_cannot_manage_documents(): void
    {
        $this->post(route('documents.store'), [
            'subject_type' => Vehicle::class,
            'subject_id' => $this->vehicle->id,
            'type' => 'stnk_pajak',
            'title' => 'Pajak Tahunan',
        ])->assertRedirect(route('login'));
    }

    public function test_user_can_create_document_with_optional_attachment(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('stnk.pdf', 300, 'application/pdf');

        $response = $this->actingAs($this->user)->post(route('documents.store'), [
            'subject_type' => Vehicle::class,
            'subject_id' => $this->vehicle->id,
            'type' => 'stnk_pajak',
            'title' => 'Pajak STNK 1 Tahunan',
            'reference_number' => 'STNK-999-XYZ',
            'issued_at' => Carbon::today()->subMonths(11)->format('Y-m-d'),
            'expires_at' => Carbon::today()->addDays(20)->format('Y-m-d'),
            'cost' => 350000,
            'notes' => 'Bayar di Samsat terdekat',
            'file' => $file,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('documents', [
            'user_id' => $this->user->id,
            'subject_type' => Vehicle::class,
            'subject_id' => $this->vehicle->id,
            'title' => 'Pajak STNK 1 Tahunan',
            'type' => 'stnk_pajak',
            'cost' => 350000,
        ]);

        $doc = Document::first();
        $this->assertNotNull($doc);
        $this->assertCount(1, $doc->attachments);
        Storage::disk('local')->assertExists($doc->attachments->first()->path);
    }

    public function test_user_cannot_create_document_for_foreign_asset(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->post(route('documents.store'), [
            'subject_type' => Vehicle::class,
            'subject_id' => $this->vehicle->id,
            'type' => 'stnk_pajak',
            'title' => 'Pajak STNK Hacker',
        ]);

        $response->assertNotFound();
    }

    public function test_document_validation_requires_title_and_type(): void
    {
        $response = $this->actingAs($this->user)->post(route('documents.store'), [
            'subject_type' => Vehicle::class,
            'subject_id' => $this->vehicle->id,
            'type' => 'invalid_type',
            'title' => '',
        ]);

        $response->assertSessionHasErrors(['title', 'type']);
    }

    public function test_user_can_update_document(): void
    {
        $doc = Document::create([
            'user_id' => $this->user->id,
            'subject_type' => HomeAsset::class,
            'subject_id' => $this->homeAsset->id,
            'type' => 'garansi',
            'title' => 'Garansi Kompresor AC',
            'expires_at' => Carbon::today()->addYears(2),
        ]);

        $response = $this->actingAs($this->user)->put(route('documents.update', $doc), [
            'title' => 'Garansi Kompresor AC 3 Tahun',
            'reference_number' => 'GAR-AC-1234',
            'expires_at' => Carbon::today()->addYears(3)->format('Y-m-d'),
            'cost' => 0,
            'notes' => 'Klaim langsung ke Daikin Center',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'id' => $doc->id,
            'title' => 'Garansi Kompresor AC 3 Tahun',
            'reference_number' => 'GAR-AC-1234',
        ]);
    }

    public function test_user_can_renew_document_and_old_one_is_archived(): void
    {
        Storage::fake('local');

        $oldDoc = Document::create([
            'user_id' => $this->user->id,
            'subject_type' => Vehicle::class,
            'subject_id' => $this->vehicle->id,
            'type' => 'asuransi',
            'title' => 'Polis TLO Garda Oto',
            'reference_number' => 'POL-001',
            'expires_at' => Carbon::yesterday(),
            'cost' => 1200000,
        ]);

        $newReceipt = UploadedFile::fake()->create('polis-baru.pdf', 200, 'application/pdf');

        $response = $this->actingAs($this->user)->post(route('documents.renew', $oldDoc), [
            'expires_at' => Carbon::today()->addYear()->format('Y-m-d'),
            'issued_at' => Carbon::today()->format('Y-m-d'),
            'cost' => 1250000,
            'notes' => 'Perpanjangan tahun ke-2',
            'file' => $newReceipt,
        ]);

        $response->assertRedirect();

        $oldDoc->refresh();
        $this->assertNotNull($oldDoc->archived_at);

        $newDoc = Document::where('id', '!=', $oldDoc->id)->first();
        $this->assertNotNull($newDoc);
        $this->assertNull($newDoc->archived_at);
        $this->assertEquals($this->vehicle->id, $newDoc->subject_id);
        $this->assertEquals('asuransi', $newDoc->type);
        $this->assertEquals('Polis TLO Garda Oto', $newDoc->title);
        $this->assertEquals(1250000, $newDoc->cost);
        $this->assertCount(1, $newDoc->attachments);
    }

    public function test_status_determination_by_expiry_thresholds(): void
    {
        $scheduleStatus = app(ScheduleStatus::class);

        // Terlambat (<= 0 hari)
        $docTerlambat = new Document(['expires_at' => Carbon::yesterday()]);
        $this->assertEquals('terlambat', $scheduleStatus->determineDocument($docTerlambat));

        // Mendesak (<= 7 hari)
        $docMendesak = new Document(['expires_at' => Carbon::today()->addDays(5)]);
        $this->assertEquals('mendesak', $scheduleStatus->determineDocument($docMendesak));

        // Perhatian (<= 30 hari)
        $docPerhatian = new Document(['expires_at' => Carbon::today()->addDays(20)]);
        $this->assertEquals('perhatian', $scheduleStatus->determineDocument($docPerhatian));

        // Normal (> 30 hari)
        $docNormal = new Document(['expires_at' => Carbon::today()->addDays(60)]);
        $this->assertEquals('normal', $scheduleStatus->determineDocument($docNormal));

        // Tanpa kadaluarsa
        $docNoExpiry = new Document(['expires_at' => null]);
        $this->assertNull($scheduleStatus->determineDocument($docNoExpiry));
    }

    public function test_attention_feed_includes_active_urgent_documents_and_excludes_archived(): void
    {
        // 1 active urgent document (expires in 3 days)
        $activeDoc = Document::create([
            'user_id' => $this->user->id,
            'subject_type' => Vehicle::class,
            'subject_id' => $this->vehicle->id,
            'type' => 'stnk_pajak',
            'title' => 'Pajak STNK Segera Habis',
            'expires_at' => Carbon::today()->addDays(3),
        ]);

        // 1 archived document (expired 10 days ago, archived)
        Document::create([
            'user_id' => $this->user->id,
            'subject_type' => Vehicle::class,
            'subject_id' => $this->vehicle->id,
            'type' => 'stnk_pajak',
            'title' => 'Pajak STNK Tahun Lalu',
            'expires_at' => Carbon::today()->subDays(10),
            'archived_at' => Carbon::today()->subDays(5),
        ]);

        $feed = app(AttentionFeed::class);
        $urgentItems = $feed->urgent($this->user);

        $this->assertTrue($urgentItems->contains(fn ($item) => $item->id === $activeDoc->id && $item->type === 'dokumen'));
        $this->assertFalse($urgentItems->contains(fn ($item) => $item->title === 'Pajak STNK Tahun Lalu'));
    }

    public function test_other_user_cannot_access_or_modify_document(): void
    {
        $otherUser = User::factory()->create();

        $doc = Document::create([
            'user_id' => $this->user->id,
            'subject_type' => Vehicle::class,
            'subject_id' => $this->vehicle->id,
            'type' => 'stnk_pajak',
            'title' => 'Pajak Rahasia',
        ]);

        $this->actingAs($otherUser)
            ->put(route('documents.update', $doc), ['title' => 'Diubah'])
            ->assertNotFound();

        $this->actingAs($otherUser)
            ->post(route('documents.renew', $doc), ['expires_at' => '2027-01-01'])
            ->assertNotFound();

        $this->actingAs($otherUser)
            ->delete(route('documents.destroy', $doc))
            ->assertNotFound();
    }
}
