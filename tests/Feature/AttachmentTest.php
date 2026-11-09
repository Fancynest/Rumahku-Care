<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\HomeAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_valid_image_and_pdf_stored_in_private_disk(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $asset = HomeAsset::create([
            'user_id' => $user->id,
            'name' => 'AC Ruang Tamu',
            'category' => 'pendingin',
            'room' => 'Ruang Tamu',
            'condition' => 'normal',
        ]);

        $fileImage = UploadedFile::fake()->image('foto-ac.jpg', 600, 400)->size(500);

        $response = $this->actingAs($user)->post('/attachments', [
            'attachable_type' => HomeAsset::class,
            'attachable_id' => $asset->id,
            'file' => $fileImage,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('attachments', [
            'user_id' => $user->id,
            'attachable_type' => HomeAsset::class,
            'attachable_id' => $asset->id,
            'original_name' => 'foto-ac.jpg',
            'mime' => 'image/jpeg',
        ]);

        $attachment = Attachment::where('original_name', 'foto-ac.jpg')->firstOrFail();
        $this->assertStringStartsWith("attachments/{$user->id}/", $attachment->path);
        Storage::disk('local')->assertExists($attachment->path);

        // Stream endpoint returns inline response with nosniff header
        $streamResponse = $this->actingAs($user)->get("/attachments/{$attachment->id}");
        $streamResponse->assertStatus(200);
        $streamResponse->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('inline', (string) $streamResponse->headers->get('Content-Disposition'));

        // Upload PDF document
        $filePdf = UploadedFile::fake()->create('buku-panduan.pdf', 800, 'application/pdf');
        $responsePdf = $this->actingAs($user)->post('/attachments', [
            'attachable_type' => HomeAsset::class,
            'attachable_id' => $asset->id,
            'file' => $filePdf,
        ]);
        $responsePdf->assertRedirect();
        $this->assertDatabaseHas('attachments', [
            'original_name' => 'buku-panduan.pdf',
            'mime' => 'application/pdf',
        ]);
    }

    public function test_upload_with_invalid_extension_or_mime_is_rejected(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $asset = HomeAsset::create([
            'user_id' => $user->id,
            'name' => 'Pompa Air',
            'category' => 'air',
            'room' => 'Belakang',
            'condition' => 'normal',
        ]);

        // .exe file disguised
        $fileExe = UploadedFile::fake()->create('script.exe', 100, 'application/x-msdownload');

        $response = $this->actingAs($user)->post('/attachments', [
            'attachable_type' => HomeAsset::class,
            'attachable_id' => $asset->id,
            'file' => $fileExe,
        ]);

        $response->assertSessionHasErrors(['file']);
        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_upload_exceeding_max_file_size_is_rejected(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $asset = HomeAsset::create([
            'user_id' => $user->id,
            'name' => 'Water Heater',
            'category' => 'kamar_mandi',
            'room' => 'Kamar Mandi',
            'condition' => 'normal',
        ]);

        // File size 6000 KB (> 5120 KB max)
        $fileTooLarge = UploadedFile::fake()->create('manual-tebal.pdf', 6000, 'application/pdf');

        $response = $this->actingAs($user)->post('/attachments', [
            'attachable_type' => HomeAsset::class,
            'attachable_id' => $asset->id,
            'file' => $fileTooLarge,
        ]);

        $response->assertSessionHasErrors(['file']);
        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_upload_exceeding_max_five_attachments_per_object_is_rejected(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $asset = HomeAsset::create([
            'user_id' => $user->id,
            'name' => 'Kulkas 2 Pintu',
            'category' => 'dapur',
            'room' => 'Dapur',
            'condition' => 'normal',
        ]);

        // Pre-create 5 attachments
        for ($i = 1; $i <= 5; $i++) {
            $asset->attachments()->create([
                'user_id' => $user->id,
                'path' => "attachments/{$user->id}/file-{$i}.jpg",
                'original_name' => "file-{$i}.jpg",
                'mime' => 'image/jpeg',
                'size' => 1024,
            ]);
        }

        $this->assertSame(5, $asset->attachments()->count());

        // Attempt 6th upload
        $sixthFile = UploadedFile::fake()->image('file-ke-6.jpg');

        $response = $this->actingAs($user)->post('/attachments', [
            'attachable_type' => HomeAsset::class,
            'attachable_id' => $asset->id,
            'file' => $sixthFile,
        ]);

        $response->assertSessionHasErrors(['file']);
        $this->assertSame(5, $asset->attachments()->count());
    }

    public function test_user_b_cannot_view_delete_or_upload_to_user_a_attachment(): void
    {
        Storage::fake('local');

        $userA = User::factory()->create(['email' => 'userA@example.com']);
        $userB = User::factory()->create(['email' => 'userB@example.com']);

        $assetA = HomeAsset::create([
            'user_id' => $userA->id,
            'name' => 'Genset Utama',
            'category' => 'listrik',
            'room' => 'Gudang',
            'condition' => 'normal',
        ]);

        $path = "attachments/{$userA->id}/rahasia.pdf";
        Storage::disk('local')->put($path, 'Konten rahasia user A');

        $attachmentA = $assetA->attachments()->create([
            'user_id' => $userA->id,
            'path' => $path,
            'original_name' => 'rahasia.pdf',
            'mime' => 'application/pdf',
            'size' => 100,
        ]);

        // User B cannot stream User A's attachment (404)
        $responseStream = $this->actingAs($userB)->get("/attachments/{$attachmentA->id}");
        $responseStream->assertStatus(404);

        // User B cannot delete User A's attachment (404)
        $responseDelete = $this->actingAs($userB)->delete("/attachments/{$attachmentA->id}");
        $responseDelete->assertStatus(404);

        // User B cannot upload an attachment to User A's asset (404)
        $file = UploadedFile::fake()->image('coba-retas.jpg');
        $responseUpload = $this->actingAs($userB)->post('/attachments', [
            'attachable_type' => HomeAsset::class,
            'attachable_id' => $assetA->id,
            'file' => $file,
        ]);
        $responseUpload->assertStatus(404);

        $this->assertDatabaseHas('attachments', ['id' => $attachmentA->id]);
    }

    public function test_physical_file_is_deleted_when_attachment_or_parent_model_is_deleted(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $asset = HomeAsset::create([
            'user_id' => $user->id,
            'name' => 'Mesin Cuci',
            'category' => 'kamar_mandi',
            'room' => 'Belakang',
            'condition' => 'normal',
        ]);

        $path1 = "attachments/{$user->id}/manual1.pdf";
        $path2 = "attachments/{$user->id}/manual2.pdf";
        Storage::disk('local')->put($path1, 'dummy data 1');
        Storage::disk('local')->put($path2, 'dummy data 2');

        $att1 = $asset->attachments()->create([
            'user_id' => $user->id,
            'path' => $path1,
            'original_name' => 'manual1.pdf',
            'mime' => 'application/pdf',
            'size' => 100,
        ]);

        $att2 = $asset->attachments()->create([
            'user_id' => $user->id,
            'path' => $path2,
            'original_name' => 'manual2.pdf',
            'mime' => 'application/pdf',
            'size' => 100,
        ]);

        Storage::disk('local')->assertExists($path1);
        Storage::disk('local')->assertExists($path2);

        // 1. Deleting single attachment removes its physical file
        $this->actingAs($user)->delete("/attachments/{$att1->id}");
        Storage::disk('local')->assertMissing($path1);
        $this->assertDatabaseMissing('attachments', ['id' => $att1->id]);

        // 2. Deleting parent model cascades and removes remaining physical files
        $asset->delete();
        Storage::disk('local')->assertMissing($path2);
        $this->assertDatabaseMissing('attachments', ['id' => $att2->id]);
    }

    public function test_attachment_stored_with_server_detected_mime_and_served_with_safe_headers(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $asset = HomeAsset::create([
            'user_id' => $user->id,
            'name' => 'Kulkas 2 Pintu',
            'category' => 'dapur',
            'room' => 'Dapur',
            'condition' => 'normal',
        ]);

        // Buat file PNG valid tapi client MIME diklaim sebagai image/jpeg
        $realPng = UploadedFile::fake()->image('gambar.png', 100, 100);
        $spoofedFile = new UploadedFile(
            $realPng->getPathname(),
            'gambar.png',
            'image/jpeg',
            null,
            true
        );

        $response = $this->actingAs($user)->post('/attachments', [
            'attachable_type' => HomeAsset::class,
            'attachable_id' => $asset->id,
            'file' => $spoofedFile,
        ]);

        $response->assertRedirect();

        // Server mendeteksi MIME asli (image/png), bukan klaim klien (image/jpeg)
        $this->assertDatabaseHas('attachments', [
            'attachable_id' => $asset->id,
            'original_name' => 'gambar.png',
            'mime' => 'image/png',
        ]);

        $attachment = Attachment::where('original_name', 'gambar.png')->firstOrFail();

        $streamResponse = $this->actingAs($user)->get("/attachments/{$attachment->id}");
        $streamResponse->assertOk();
        $streamResponse->assertHeader('Content-Type', 'image/png');
        $streamResponse->assertHeader('X-Content-Type-Options', 'nosniff');
        $streamResponse->assertHeader('Content-Security-Policy', "sandbox; default-src 'none'");
        $this->assertStringContainsString('inline', (string) $streamResponse->headers->get('Content-Disposition'));
        $this->assertStringContainsString('filename=gambar.png', (string) $streamResponse->headers->get('Content-Disposition'));
    }
}
