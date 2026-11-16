<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $input = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginValue = $input['email'];
        $isEmail = filter_var($loginValue, FILTER_VALIDATE_EMAIL);
        $credentials = [
            $isEmail ? 'email' : 'name' => $loginValue,
            'password' => $input['password'],
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();
            app(ActivityLogger::class)->log(
                $user,
                'auth.login',
                $user,
                'Masuk ke sistem (Login berhasil).',
                [
                    'email' => $user->email,
                ]
            );

            return redirect()->intended(route('dashboard'));
        }

        // Catat jika ada percobaan login yang gagal untuk email/username terdaftar
        $failedUser = User::where('email', $loginValue)->orWhere('name', $loginValue)->first();
        if ($failedUser) {
            app(ActivityLogger::class)->log(
                $failedUser,
                'auth.failed',
                $failedUser,
                'Percobaan masuk gagal (kata sandi salah).',
                [
                    'email' => $failedUser->email,
                ]
            );
        }

        throw ValidationException::withMessages([
            'email' => 'Email/nama pengguna atau kata sandi yang Anda masukkan salah.',
        ]);
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        app(ActivityLogger::class)->log(
            $user,
            'auth.register',
            $user,
            'Pendaftaran akun baru berhasil.',
            [
                'name' => $user->name,
                'email' => $user->email,
            ]
        );

        return redirect()->route('dashboard')
            ->with('success', 'Akun berhasil dibuat. Selamat datang di Rumahku Care!');
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            app(ActivityLogger::class)->log(
                $user,
                'auth.logout',
                $user,
                'Keluar dari sistem (Logout).',
                [
                    'email' => $user->email,
                ]
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::broker()->sendResetLink(
            $request->only('email')
        );

        // Jangan membocorkan apakah email terdaftar (CWE-204 / anti user enumeration)
        return back()->with('status', 'Jika alamat email terdaftar di sistem kami, tautan pemulihan kata sandi telah dikirim.');
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                app(ActivityLogger::class)->log(
                    $user,
                    'auth.password_reset',
                    $user,
                    'Kata sandi berhasil diatur ulang via tautan lupa sandi.',
                    [
                        'email' => $user->email,
                    ]
                );
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('success', 'Kata sandi berhasil diatur ulang. Silakan masuk dengan kata sandi baru Anda.');
        }

        throw ValidationException::withMessages([
            'email' => [trans($status) ?: 'Tautan reset kata sandi tidak valid atau telah kedaluwarsa.'],
        ]);
    }
}
