<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required|string',
                'password' => 'required|string',
            ]);

            $user = AdminUser::where('username', $request->username)->first();

            if (!$user) {
                Log::warning('Login failed: User not found', ['username' => $request->username]);
                return response()->json([
                    'message' => 'Username atau password salah'
                ], 401);
            }

            // Debug: Check password info
            $passwordInfo = Hash::info($user->password);
            Log::debug('Password info', [
                'username' => $user->username,
                'algorithm' => $passwordInfo['algoName'] ?? 'unknown',
                'options' => $passwordInfo['options'] ?? []
            ]);

            // Check password
            if (!Hash::check($request->password, $user->password)) {
                Log::warning('Login failed: Password mismatch', [
                    'username' => $request->username,
                    'input_password' => $request->password,
                    'stored_hash' => $user->password
                ]);
                return response()->json([
                    'message' => 'Username atau password salah'
                ], 401);
            }

            // Create token with admin scope
            $token = $user->createToken('auth_token', ['admin'])->plainTextToken;

            Log::info('Login successful', ['username' => $user->username, 'user_id' => $user->id]);

            return response()->json([
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Login error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Terjadi kesalahan sistem'
            ], 500);
        }
    }

    public function register(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required|string|min:3|unique:admin_users,username',
                'password' => 'required|string|min:6',
            ]);

            // Hash password
            $hashedPassword = Hash::make($request->password);
            
            Log::info('Register attempt', [
                'username' => $request->username,
                'hashed_password' => $hashedPassword,
                'password_info' => Hash::info($hashedPassword)
            ]);

            // Create user
            $user = AdminUser::create([
                'username' => $request->username,
                'password' => $hashedPassword,
            ]);

            Log::info('User registered successfully', [
                'user_id' => $user->id,
                'username' => $user->username
            ]);

            return response()->json([
                'message' => 'Admin berhasil didaftarkan',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Register error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Gagal mendaftarkan admin'
            ], 500);
        }
    }

    public function adminOnly(Request $request)
    {
        try {
            return response()->json([
                'message' => "Selamat datang admin {$request->user()->username}",
                'user' => [
                    'id' => $request->user()->id,
                    'username' => $request->user()->username
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Admin only endpoint error', [
                'message' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Terjadi kesalahan sistem'
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            $username = $user->username;
            
            $user->currentAccessToken()->delete();

            Log::info('Logout successful', ['username' => $username]);

            return response()->json([
                'message' => 'Berhasil logout'
            ]);

        } catch (\Exception $e) {
            Log::error('Logout error', [
                'message' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Gagal logout'
            ], 500);
        }
    }

    /**
     * Utility method untuk debug password issues
     */
    public function debugPassword(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required|string',
                'password' => 'required|string',
            ]);

            $user = AdminUser::where('username', $request->username)->first();

            if (!$user) {
                return response()->json([
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            $passwordInfo = Hash::info($user->password);
            $checkResult = Hash::check($request->password, $user->password);

            return response()->json([
                'user_exists' => true,
                'stored_hash' => $user->password,
                'hash_algorithm' => $passwordInfo['algoName'] ?? 'unknown',
                'hash_options' => $passwordInfo['options'] ?? [],
                'password_match' => $checkResult,
                'debug_info' => [
                    'input_password' => $request->password,
                    'hash_check_result' => $checkResult
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Debug error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}