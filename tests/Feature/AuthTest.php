<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');

        $responseVehicles = $this->get('/vehicles');
        $responseVehicles->assertRedirect('/login');
    }

    public function test_guest_does_not_see_sidebar_or_top_header_on_auth_pages(): void
    {
        $loginRes = $this->get('/login');
        $loginRes->assertOk();
        $loginRes->assertSee('Masuk ke Rumahku Care');
        $loginRes->assertDontSee('id="sidebar"', false);
        $loginRes->assertDontSee('class="sidebar', false);
        $loginRes->assertDontSee('top-header', false);

        $registerRes = $this->get('/register');
        $registerRes->assertOk();
        $registerRes->assertSee('Daftar Akun Baru');
        $registerRes->assertDontSee('id="sidebar"', false);
        $registerRes->assertDontSee('class="sidebar', false);
        $registerRes->assertDontSee('top-header', false);
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::firstOrFail();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);

        $logoutResponse = $this->post('/logout');
        $logoutResponse->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_user_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/');
        $this->assertDatabaseHas('users', ['email' => 'budi@example.com']);
        $this->assertAuthenticated();
    }

    public function test_user_a_cannot_access_or_update_user_b_vehicle(): void
    {
        $userA = User::firstOrFail();
        $userB = User::factory()->create([
            'email' => 'userb@example.com',
        ]);

        $vehicleUserB = Vehicle::create([
            'user_id' => $userB->id,
            'plate' => 'D 1111 TST',
            'odometer' => 5000,
            'color_key' => 'hitam',
            'fuel_type' => 'Bensin',
        ]);

        // User A cannot update User B's vehicle odometer (returns 404)
        $response = $this->actingAs($userA)->patch("/vehicles/{$vehicleUserB->id}/odometer", [
            'odometer' => 6000,
        ]);
        $response->assertStatus(404);

        // User A cannot store service log for User B's vehicle (returns 404)
        $responseLog = $this->actingAs($userA)->post("/vehicles/{$vehicleUserB->id}/service-logs", [
            'serviced_at' => '2026-11-09',
            'odometer' => 6000,
        ]);
        $responseLog->assertStatus(404);

        // In User A's garage index, User B's vehicle is not listed
        $garageResponse = $this->actingAs($userA)->get('/vehicles');
        $garageResponse->assertDontSee('D 1111 TST');
    }

    public function test_login_rate_limiting_throttles_after_five_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->from('/login')->post('/login', [
                'email' => 'throttletest@example.com',
                'password' => 'wrongpassword',
            ]);
            $response->assertRedirect('/login');
            $response->assertSessionHasErrors(['email']);
        }

        // 6th attempt should be redirected back with throttle error on email field
        $response6 = $this->from('/login')->post('/login', [
            'email' => 'throttletest@example.com',
            'password' => 'wrongpassword',
        ]);

        $response6->assertRedirect('/login');
        $response6->assertSessionHasErrors(['email']);

        $errorMsg = session('errors')->first('email');
        $this->assertStringContainsString('Terlalu banyak percobaan. Coba lagi dalam', $errorMsg);
        $this->assertStringContainsString('detik.', $errorMsg);
    }

    public function test_forgot_password_page_is_accessible_and_hides_sidebar(): void
    {
        $response = $this->get('/forgot-password');
        $response->assertOk();
        $response->assertSee('Lupa Kata Sandi');
        $response->assertDontSee('id="sidebar"', false);
        $response->assertDontSee('top-header', false);
    }

    public function test_forgot_password_sends_generic_message_without_user_enumeration(): void
    {
        $user = User::firstOrFail();

        // 1. Existing user email
        $resExisting = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);
        $resExisting->assertRedirect();
        $resExisting->assertSessionHas('status', 'Jika alamat email terdaftar di sistem kami, tautan pemulihan kata sandi telah dikirim.');

        // 2. Non-existent user email returns exact same message (anti user-enumeration)
        $resNonExisting = $this->post('/forgot-password', [
            'email' => 'nobody_registered@example.com',
        ]);
        $resNonExisting->assertRedirect();
        $resNonExisting->assertSessionHas('status', 'Jika alamat email terdaftar di sistem kami, tautan pemulihan kata sandi telah dikirim.');
    }

    public function test_forgot_password_is_throttled_after_five_requests(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/forgot-password', ['email' => "throttle{$i}@example.com"]);
        }

        $response6 = $this->post('/forgot-password', ['email' => 'throttle6@example.com']);
        $response6->assertRedirect();
        $response6->assertSessionHasErrors(['email']);
        $errorMsg = session('errors')->first('email');
        $this->assertStringContainsString('Terlalu banyak percobaan. Coba lagi dalam', $errorMsg);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::firstOrFail();
        $token = Password::broker()->createToken($user);

        $resetPage = $this->get("/reset-password/{$token}?email=".urlencode($user->email));
        $resetPage->assertOk();
        $resetPage->assertSee('Atur Ulang Kata Sandi');

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('success');

        // Can login with new password
        $loginRes = $this->post('/login', [
            'email' => $user->email,
            'password' => 'new-password123',
        ]);
        $loginRes->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_reset_password_with_invalid_token(): void
    {
        $user = User::firstOrFail();

        $response = $this->post('/reset-password', [
            'token' => 'invalid-token-12345',
            'email' => $user->email,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_reset_password_is_throttled_after_five_attempts(): void
    {
        $user = User::firstOrFail();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/reset-password', [
                'token' => 'some-token',
                'email' => $user->email,
                'password' => 'new-password123',
                'password_confirmation' => 'new-password123',
            ]);
        }

        $response6 = $this->post('/reset-password', [
            'token' => 'some-token',
            'email' => $user->email,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response6->assertRedirect();
        $response6->assertSessionHasErrors(['email']);
        $errorMsg = session('errors')->first('email');
        $this->assertStringContainsString('Terlalu banyak percobaan. Coba lagi dalam', $errorMsg);
    }

    public function test_register_rate_limiting_throttles_after_five_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->from('/register')->post('/register', [
                'name' => 'User Failed',
                'email' => 'invalid-email',
                'password' => 'short',
                'password_confirmation' => 'mismatch',
            ]);
            $response->assertRedirect('/register');
        }

        // 6th attempt should be blocked by throttle
        $response6 = $this->from('/register')->post('/register', [
            'name' => 'User Sixth',
            'email' => 'valid@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response6->assertRedirect('/register');
        $response6->assertSessionHasErrors(['email']);
        $errorMsg = session('errors')->first('email');
        $this->assertStringContainsString('Terlalu banyak percobaan. Coba lagi dalam', $errorMsg);
    }
}
