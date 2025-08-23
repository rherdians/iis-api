<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = AdminUser::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Username atau password salah'
            ], 401);
        }

        $token = $user->createToken('auth_token', ['admin'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
            ]
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|min:3|unique:admin_users,username',
            'password' => 'required|string|min:6',
        ]);

        $user = AdminUser::create([
            'username' => $request->username,
            'password' => Hash::make($request->password), // penting! 
        ]);

        return response()->json([
            'message' => 'Admin berhasil didaftarkan'
        ], 201);
    }

    public function adminOnly(Request $request)
    {
        return response()->json([
            'message' => "Selamat datang admin {$request->user()->username}"
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Berhasil logout'
        ]);
    }
}
