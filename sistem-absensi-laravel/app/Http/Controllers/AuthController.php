<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    // ── GET /login ────────────────────────────────────────────────────────────
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user());
        }
        return view('auth.login');
    }

    // ── POST /login ───────────────────────────────────────────────────────────
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:50',
            'password' => 'required|string',
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $user = User::where('username', $request->username)->first();

        // Akun tidak ditemukan
        if (!$user) {
            return back()
                ->withInput(['username' => $request->username])
                ->withErrors(['username' => 'Akun tidak ditemukan.']);
        }

        // Akun non-aktif
        if ((int) $user->aktif !== 1) {
            return back()
                ->withInput(['username' => $request->username])
                ->withErrors(['username' => 'Akun tidak aktif. Hubungi administrator.']);
        }

        // Verifikasi password — support bcrypt DAN plain-text lama
        $passwordOk = false;
        if (strlen($user->password) >= 60 && str_starts_with($user->password, '$2y$')) {
            $passwordOk = \Illuminate\Support\Facades\Hash::check($request->password, $user->password);
        } else {
            // Legacy plain-text (dari sistem lama)
            $passwordOk = hash_equals($user->password, $request->password);
        }

        if (!$passwordOk) {
            return back()
                ->withInput(['username' => $request->username])
                ->withErrors(['password' => 'Kata sandi tidak valid.']);
        }

        // Login berhasil
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return $this->redirectByRole($user);
    }

    // ── POST /logout ──────────────────────────────────────────────────────────
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Berhasil keluar dari sistem.');
    }

    // ── Private helper ────────────────────────────────────────────────────────
    private function redirectByRole(User $user)
    {
        return $user->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('user.dashboard');
    }
}

