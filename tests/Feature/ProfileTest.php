<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\HomeAsset;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_cannot_access_profile_page(): void
    {
        $response = $this->get('/profile');
        $response->assertRedirect('/login');
    }

    public function test_profile_page_is_accessible_by_authenticated_user(): void
    {
        $user = User::firstOrFail();
        $this->actingAs($user);

        $response = $this->get('/profile');
        $response->assertOk();
        $response->assertSee('Pengaturan Profil');
        $response->assertSee($user->name);
        $response->assertSee($user->email);
    }

    public function test_user_can_update_name_without_current_password(): void
    {
        $user = User::firstOrFail();
        $this->actingAs($user);

        $response = $this->patch('/profile', [
            'name' => 'Nama Baru User',
            'email' => $user->email,
        ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Nama Baru User', $user->name);
    }

    public function test_user_must_provide_current_password_when_changing_email(): void
    {
        $user = User::firstOrFail();
        $this->actingAs($user);

        $response = $this->patch('/profile', [
            'name' => $user->name,
            'email' => 'newemail@example.com',
            // without current_password
        ]);

        $response->assertSessionHasErrors(['current_password']);
        $user->refresh();
        $this->assertNotSame('newemail@example.com', $user->email);
    }

    public function test_user_cannot_change_email_with_invalid_current_password(): void
    {
        $user = User::firstOrFail();
        $this->actingAs($user);

        $response = $this->patch('/profile', [
            'name' => $user->name,
            'email' => 'newemail@example.com',
            'current_password' => 'wrong-pass',
        ]);

        $response->assertSessionHasErrors(['current_password']);
        $user->refresh();
        $this->assertNotSame('newemail@example.com', $user->email);
    }

    public function test_user_can_change_email_with_valid_current_password(): void
    {
        $user = User::firstOrFail();
        $this->actingAs($user);

        $response = $this->patch('/profile', [
            'name' => $user->name,
            'email' => 'updated_email@example.com',
            'current_password' => 'password',
        ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('updated_email@example.com', $user->email);
    }

    public function test_cannot_change_email_to_an_already_registered_email(): void
    {
        $userA = User::firstOrFail();
        $userB = User::factory()->create([
            'email' => 'registered_user@example.com',
        ]);

        $this->actingAs($userA);

        $response = $this->patch('/profile', [
            'name' => $userA->name,
            'email' => 'registered_user@example.com',
            'current_password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_user_can_update_password_and_invalidates_other_devices(): void
    {
        $user = User::firstOrFail();
        $this->actingAs($user);

        $response = $this->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'brand-new-secret-123',
            'password_confirmation' => 'brand-new-secret-123',
        ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('brand-new-secret-123', $user->password));
    }

    public function test_cannot_update_password_with_incorrect_current_password(): void
    {
        $user = User::firstOrFail();
        $this->actingAs($user);

        $response = $this->put('/profile/password', [
            'current_password' => 'wrongpassword',
            'password' => 'brand-new-secret-123',
            'password_confirmation' => 'brand-new-secret-123',
        ]);

        $response->assertSessionHasErrors(['current_password']);
    }

    public function test_cannot_update_password_with_mismatched_confirmation(): void
    {
        $user = User::firstOrFail();
        $this->actingAs($user);

        $response = $this->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'brand-new-secret-123',
            'password_confirmation' => 'mismatch-123',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_user_cannot_delete_account_with_incorrect_password(): void
    {
        $user = User::firstOrFail();
        $this->actingAs($user);

        $response = $this->delete('/profile', [
            'password' => 'wrong-pass',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_user_can_delete_account_and_cascades_all_data_and_physical_files(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'email' => 'user_to_delete@example.com',
            'password' => 'secret123',
        ]);

        // Beri data aset, kendaraan, dan file fisik
        $asset = HomeAsset::create([
            'user_id' => $user->id,
            'name' => 'AC Kamar Delete',
            'room' => 'Kamar Tidur',
            'category' => 'pendingin',
            'condition' => 'normal',
        ]);

        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'plate' => 'B 9999 DEL',
            'odometer' => 1000,
            'color_key' => 'hitam',
            'fuel_type' => 'Bensin',
        ]);

        // Upload fake file ke storage user
        $fakeFile = UploadedFile::fake()->create('nota.pdf', 100, 'application/pdf');
        $path = $fakeFile->store("attachments/{$user->id}", 'local');

        $attachment = Attachment::create([
            'user_id' => $user->id,
            'attachable_type' => 'home_asset',
            'attachable_id' => $asset->id,
            'path' => $path,
            'original_name' => 'nota.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
        ]);

        // Tambahkan baris sesi dan password_reset_tokens milik pengguna
        DB::table('sessions')->insert([
            'id' => 'dummy_session_user_to_delete',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'dummy',
            'last_activity' => time(),
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => 'dummy_reset_token',
            'created_at' => now(),
        ]);

        Storage::disk('local')->assertExists($path);

        $this->actingAs($user);

        $response = $this->delete('/profile', [
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();

        // Database records must be completely gone
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('home_assets', ['id' => $asset->id]);
        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        // Storage directory & files must be deleted
        Storage::disk('local')->assertMissing($path);
        $this->assertEmpty(Storage::disk('local')->allFiles("attachments/{$user->id}"));
    }

    public function test_physical_files_are_preserved_if_deletion_fails(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'email' => 'user_rollback@example.com',
            'password' => 'secret123',
        ]);

        $fakeFile = UploadedFile::fake()->create('nota_safe.pdf', 100, 'application/pdf');
        $path = $fakeFile->store("attachments/{$user->id}", 'local');

        $attachment = Attachment::create([
            'user_id' => $user->id,
            'attachable_type' => 'user',
            'attachable_id' => $user->id,
            'path' => $path,
            'original_name' => 'nota_safe.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
        ]);

        Storage::disk('local')->assertExists($path);

        $this->actingAs($user);

        $response = $this->delete('/profile', [
            'password' => 'wrong-pass',
        ]);

        $response->assertSessionHasErrors(['password']);
        Storage::disk('local')->assertExists($path);
        $this->assertDatabaseHas('attachments', ['id' => $attachment->id]);
    }
}
