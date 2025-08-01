<?php
// app/Http/Controllers/ReferralController.php

namespace App\Http\Controllers;

use App\Models\ReferralCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReferralController extends Controller
{
    public function index()
    {
        $referralCodes = ReferralCode::orderBy('created_at', 'desc')->get();
        
        return response()->json($referralCodes);
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_referal' => 'required|string|unique:referral_codes,kode_referal',
            'username' => 'required|string',
        ], [
            'kode_referal.required' => 'Kode referal wajib diisi',
            'kode_referal.unique' => 'Kode referal sudah digunakan',
            'username.required' => 'Username wajib diisi',
        ]);

        ReferralCode::create([
            'kode_referal' => $request->kode_referal,
            'username' => $request->username,
            'usage' => 0,
        ]);

        return response()->json([
            'message' => 'Kode referal berhasil ditambahkan'
        ], 201);
    }

    public function destroy($id)
    {
        if (!is_numeric($id)) {
            return response()->json([
                'message' => 'ID harus berupa angka yang valid'
            ], 400);
        }

        $referralCode = ReferralCode::find($id);

        if (!$referralCode) {
            return response()->json([
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $referralCode->delete();

        return response()->json([
            'message' => 'Kode referal berhasil dihapus'
        ]);
    }

    public function useReferral($kode_referal)
    {
        if (empty(trim($kode_referal))) {
            return response()->json([
                'message' => 'Kode referal tidak valid'
            ], 400);
        }

        $referralCode = ReferralCode::where('kode_referal', $kode_referal)->first();

        if (!$referralCode) {
            return response()->json([
                'message' => 'Kode referal tidak ditemukan'
            ], 404);
        }

        $referralCode->increment('usage');
        $referralCode->touch(); // Update updated_at

        return response()->json([
            'message' => 'Usage berhasil ditambahkan'
        ]);
    }
}