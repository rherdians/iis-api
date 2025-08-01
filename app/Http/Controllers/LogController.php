<?php
// app/Http/Controllers/LogController.php

namespace App\Http\Controllers;

use App\Models\ReferralLog;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'book_title' => 'nullable|string',
            'referral_code' => 'nullable|string',
            'user_agent' => 'nullable|string',
            'nama_pembeli' => 'nullable|string',
            'alamat' => 'nullable|string',
            'nomor_pembeli' => 'nullable|string',
            'harga' => 'nullable|numeric',
            'harga_asli' => 'nullable|numeric',
            'diskon_amount' => 'nullable|numeric',
        ]);

        $ip = $request->header('X-Forwarded-For') ?: $request->ip();

        $log = ReferralLog::create([
            'book_title' => $request->book_title,
            'referral_code' => $request->referral_code,
            'user_agent' => $request->user_agent,
            'ip_address' => $ip,
            'status' => 'belum beli',
            'nama_pembeli' => $request->nama_pembeli,
            'alamat' => $request->alamat,
            'nomor_pembeli' => $request->nomor_pembeli,
            'harga' => $request->harga,
            'harga_asli' => $request->harga_asli,
            'diskon_amount' => $request->diskon_amount,
        ]);

        return response()->json([
            'message' => 'Click logged successfully',
            'id' => $log->id
        ]);
    }

    public function updateOrderId(Request $request, $id)
    {
        $request->validate([
            'order_id' => 'required|string'
        ]);

        $log = ReferralLog::find($id);

        if (!$log) {
            return response()->json([
                'error' => 'Log not found'
            ], 404);
        }

        $log->update(['order_id' => $request->order_id]);

        return response()->json([
            'message' => 'Order ID updated successfully',
            'order_id' => $request->order_id
        ]);
    }

    public function index()
    {
        $logs = ReferralLog::select([
            'id', 'book_title', 'referral_code', 'order_id', 
            'user_agent', 'ip_address', 'whatsapp_click_time', 
            'status', 'nama_pembeli', 'alamat', 'nomor_pembeli', 
            'harga', 'harga_asli', 'diskon_amount', 'created_at'
        ])->orderBy('created_at', 'desc')->get();

        return response()->json($logs);
    }

    public function update(Request $request, $id)
    {
        if (!is_numeric($id)) {
            return response()->json([
                'error' => 'ID must be numeric'
            ], 400);
        }

        $allowedStatuses = ['beli', 'belum beli', 'pending', 'gagal', 'challenge'];
        
        $request->validate([
            'status' => 'required|in:' . implode(',', $allowedStatuses),
            'order_id' => 'nullable|string',
            'payment_type' => 'nullable|string',
            'paid_at' => 'nullable|date',
            'failure_reason' => 'nullable|string',
        ]);

        $log = ReferralLog::find($id);

        if (!$log) {
            return response()->json([
                'error' => 'Log tidak ditemukan'
            ], 404);
        }

        $updateData = ['status' => $request->status];

        if ($request->has('order_id')) {
            $updateData['order_id'] = $request->order_id;
        }
        if ($request->has('payment_type')) {
            $updateData['payment_type'] = $request->payment_type;
        }
        if ($request->has('paid_at')) {
            $updateData['paid_at'] = $request->paid_at;
        }
        if ($request->has('failure_reason')) {
            $updateData['failure_reason'] = $request->failure_reason;
        }

        $log->update($updateData);

        return response()->json([
            'message' => 'Status berhasil diperbarui',
            'affected_rows' => 1
        ]);
    }
}