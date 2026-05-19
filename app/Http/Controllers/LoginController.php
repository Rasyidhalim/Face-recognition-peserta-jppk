<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Menangani proses login petugas
     */
    public function login(Request $request)
    {
        // 1. Validasi inputan dari Vue
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // 2. Kumpulkan data login
        $credentials = $request->only('username', 'password');

        // 3. Cek ke Database (Laravel otomatis nge-hash password di sini)
        if (Auth::attempt($credentials)) {
            // Jika sukses, buat session baru
            $request->session()->regenerate();

            $user = Auth::user();

            // Kirim data user & role ke Vue
            return response()->json([
                'status' => 'success',
                'message' => 'Login Berhasil!',
                'user' => [
                    'name' => $user->name,
                    'role' => $user->role, // loket / farmasi / admin
                ]
            ]);
        }

        // 4. Jika gagal, kirim error
        return response()->json([
            'status' => 'error',
            'message' => 'Username atau password salah.'
        ], 401);
    }

    /**
     * Menangani proses logout
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil keluar dari sistem.'
        ]);
    }
}