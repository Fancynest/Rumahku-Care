<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ];

        // Jika mengubah email, wajib memasukkan kata sandi saat ini
        if ($request->input('email') !== $user->email) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $validated = $request->validate($rules, [
            'current_password.current_password' => 'Kata sandi saat ini tidak cocok.',
            'current_password.required' => 'Kata sandi saat ini wajib diisi untuk mengubah alamat email.',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        return redirect()->route('profile.edit')
            ->with('success', 'Profil Anda berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.current_password' => 'Kata sandi saat ini tidak cocok.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'password.min' => 'Kata sandi baru minimal harus 8 karakter.',
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Invalidate sesi di perangkat lain
        Auth::logoutOtherDevices($validated['password']);

        return redirect()->route('profile.edit')
            ->with('success', 'Kata sandi berhasil diperbarui dan sesi di perangkat lain telah dinonaktifkan.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ], [
            'password.current_password' => 'Kata sandi yang Anda masukkan salah.',
        ]);

        $user = $request->user();
        $userId = $user->id;
        $userEmail = $user->email;
        $userDirectory = "attachments/{$userId}";

        // Kumpulkan path berkas fisik lampiran milik pengguna sebelum baris DB dihapus
        $attachmentPaths = $user->attachments()->pluck('path')->filter()->values()->all();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        DB::transaction(function () use ($user, $userId, $userEmail): void {
            DB::table('sessions')->where('user_id', $userId)->delete();
            DB::table('password_reset_tokens')->where('email', $userEmail)->delete();

            $user->delete();
        });

        // Hapus file fisik SETELAH transaction DB commit
        foreach ($attachmentPaths as $path) {
            if (Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }
        }
        if (Storage::disk('local')->exists($userDirectory)) {
            Storage::disk('local')->deleteDirectory($userDirectory);
        }

        return redirect()->route('login')
            ->with('success', 'Akun Anda beserta seluruh data dan berkas telah berhasil dihapus.');
    }
}
