<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Validasi input
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // Cek apakah email & password sesuai
        if (!Auth::guard('web')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Login gagal, email atau password salah.',
            ], 401);
        }

        // Ambil data admin
        $admin = Admin::where('email', $request->email)->first();

        // Buat token Sanctum
        $token = $admin->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'token'   => $token,
            'admin'   => [
                'id'    => $admin->id,
                'name'  => $admin->name,
                'email' => $admin->email,
            ]
        ]);
    }
}
