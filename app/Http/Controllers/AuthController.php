<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'user_id' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $loginField = filter_var($credentials['user_id'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Cek jika akun ada namun dinonaktifkan
        $existingUser = User::where($loginField, $credentials['user_id'])->first();
        if ($existingUser && ! $existingUser->is_active && Hash::check($credentials['password'], $existingUser->password)) {
            return back()->withErrors([
                'user_id' => 'Akun Anda sedang dinonaktifkan. Silakan hubungi administrator.',
            ])->onlyInput('user_id');
        }

        if (Auth::attempt([$loginField => $credentials['user_id'], 'password' => $credentials['password'], 'is_active' => true], $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended('/beranda');
        }

        return back()->withErrors([
            'user_id' => 'The provided credentials do not match our records.',
        ])->onlyInput('user_id');
    }

    public function updateForceChangePassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        return back()->with('success', 'Kata sandi baru berhasil dibuat. Selamat datang di sistem!');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
