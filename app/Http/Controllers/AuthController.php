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

    if (!$user) {
        return response()->json([
            'message' => 'Username atau password salah'
        ], 401);
    }

    // Check if password uses bcrypt
    $passwordInfo = Hash::info($user->password);
    
    if ($passwordInfo['algo'] !== '2y') {
        // Legacy password - use your old hashing method
        if ($this->checkLegacyPassword($request->password, $user->password)) {
            // Upgrade legacy password to bcrypt
            $user->password = Hash::make($request->password);
            $user->save();
        } else {
            return response()->json([
                'message' => 'Username atau password salah'
            ], 401);
        }
    } else {
        // Modern bcrypt password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Username atau password salah'
            ], 401);
        }
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

private function checkLegacyPassword($inputPassword, $storedHash)
{
    // Deteksi jenis hash berdasarkan pattern
    $length = strlen($storedHash);
    
    // MD5 (32 karakter hex)
    if ($length === 32 && ctype_xdigit($storedHash)) {
        return md5($inputPassword) === $storedHash;
    }
    
    // SHA1 (40 karakter hex)
    if ($length === 40 && ctype_xdigit($storedHash)) {
        return sha1($inputPassword) === $storedHash;
    }
    
    // Base64 encoded (bisa berbagai algoritma)
    if (base64_encode(base64_decode($storedHash)) === $storedHash) {
        // Coba beberapa kemungkinan
        if (md5($inputPassword) === base64_decode($storedHash)) {
            return true;
        }
        if (sha1($inputPassword) === base64_decode($storedHash)) {
            return true;
        }
    }
    
    // Plain text
    if ($inputPassword === $storedHash) {
        return true;
    }
    
    return false;
}


   public function register(Request $request)
{
    $request->validate([
        'username' => 'required|string|min:3|unique:admin_users,username',
        'password' => 'required|string|min:6',
    ]);

    // Debug: Check what Hash::make produces
    $hashedPassword = Hash::make($request->password);
    \Log::info('Hashed password: ' . $hashedPassword);
    \Log::info('Password info: ' . print_r(Hash::info($hashedPassword), true));

    $user = AdminUser::create([
        'username' => $request->username,
        'password' => $hashedPassword,
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
