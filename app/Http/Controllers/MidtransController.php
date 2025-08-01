<?php
// app/Http/Controllers/MidtransController.php

namespace App\Http\Controllers;

use App\Models\ReferralLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransController extends Controller
{
    private $serverKey;
    private $clientKey;
    private $isProduction = true;
    private $snapUrl = 'https://app.midtrans.com/snap/v1/transactions';
    private $baseUrl = 'https://api.midtrans.com/v2';

    public function __construct()
    {
        $this->serverKey = config('services.midtrans.server_key');
        $this->clientKey = config('services.midtrans.client_key');

        if (!$this->serverKey || !$this->clientKey) {
            Log::error('Midtrans credentials not configured');
            throw new \Exception('Midtrans credentials required');
        }
    }

    private function getAuthHeader()
    {
        return 'Basic ' . base64_encode($this->serverKey . ':');
    }

    private function isValidEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function isValidPhoneNumber($phone)
    {
        return preg_match('/^(\+62|62|0)[0-9]{8,13}$/', $phone);
    }

    public function createTransaction(Request $request)
    {
        $validationRules = [
            'nama' => 'required|string|min:2',
            'nomor' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'item_name' => 'required|string|min:1',
            'customer_email' => 'nullable|email',
            'alamat' => 'nullable|string',
            'referral_code' => 'nullable|string',
            'log_id' => 'nullable|numeric',
        ];

        $validationMessages = [
            'nama.required' => 'Nama wajib diisi',
            'nama.min' => 'Nama minimal 2 karakter',
            'nomor.required' => 'Nomor telepon wajib diisi',
            'amount.required' => 'Amount wajib diisi',
            'amount.numeric' => 'Amount harus berupa angka',
            'amount.min' => 'Amount harus lebih dari 0',
            'item_name.required' => 'Nama item wajib diisi',
            'customer_email.email' => 'Format email tidak valid',
        ];

        $request->validate($validationRules, $validationMessages);

        if (!$this->isValidPhoneNumber($request->nomor)) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor telepon tidak valid'
            ], 400);
        }

        $timestamp = now()->timestamp;
        $orderId = $request->log_id ? "ORDER-{$timestamp}-{$request->log_id}" : "ORDER-{$timestamp}";
        $customerEmail = $request->customer_email ?: "customer_{$timestamp}@temp.local";

        // Format phone number
        $formattedPhone = $request->nomor;
        if (str_starts_with($formattedPhone, '0')) {
            $formattedPhone = '+62' . substr($formattedPhone, 1);
        } elseif (str_starts_with($formattedPhone, '62')) {
            $formattedPhone = '+' . $formattedPhone;
        } elseif (!str_starts_with($formattedPhone, '+62')) {
            $formattedPhone = '+62' . $formattedPhone;
        }

        $payload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $request->amount
            ],
            'customer_details' => [
                'first_name' => trim($request->nama),
                'email' => $customerEmail,
                'phone' => $formattedPhone,
                'billing_address' => [
                    'first_name' => trim($request->nama),
                    'address' => $request->alamat ?: 'Not provided',
                    'city' => 'Jakarta',
                    'postal_code' => '12345',
                    'country_code' => 'IDN'
                ],
                'shipping_address' => [
                    'first_name' => trim($request->nama),
                    'address' => $request->alamat ?: 'Not provided',
                    'city' => 'Jakarta',
                    'postal_code' => '12345',
                    'country_code' => 'IDN'
                ]
            ],
            'item_details' => [
                [
                    'id' => "ITEM-{$timestamp}",
                    'price' => (int) $request->amount,
                    'quantity' => 1,
                    'name' => trim($request->item_name),
                    'category' => 'book'
                ]
            ],
            'custom_field1' => $request->referral_code ?: '',
            'custom_field2' => $request->log_id ?: '',
            'enabled_payments' => [
                'credit_card', 'mandiri_clickpay', 'cimb_clicks',
                'bca_klikbca', 'bca_klikpay', 'bri_epay', 'echannel',
                'permata_va', 'bca_va', 'bni_va', 'bri_va', 'other_va',
                'gopay', 'shopeepay', 'indomaret', 'danamon_online',
                'akulaku', 'qris'
            ],
            'callbacks' => [
                'finish' => config('app.url') . '/payment-success'
            ]
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->getAuthHeader(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->timeout(30)->post($this->snapUrl, $payload);

            if ($response->successful() && $response->json('token')) {
                Log::info('Snap transaction created successfully (PRODUCTION)', ['order_id' => $orderId]);
                
                return response()->json([
                    'success' => true,
                    'order_id' => $orderId,
                    'token' => $response->json('token'),
                    'redirect_url' => $response->json('redirect_url'),
                    'client_key' => $this->clientKey,
                    'is_production' => $this->isProduction
                ]);
            } else {
                Log::error('Midtrans Snap API error', $response->json());
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membuat transaksi',
                    'error' => $response->json('status_message') ?: 'Unknown error'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Create Snap transaction error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = 'Gagal membuat transaksi Midtrans';
            $statusCode = 500;

            if (str_contains($e->getMessage(), 'timeout')) {
                $errorMessage = 'Request timeout ke server Midtrans';
            } elseif (str_contains($e->getMessage(), 'Connection refused')) {
                $errorMessage = 'Tidak dapat terhubung ke server Midtrans';
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error' => app()->environment('local') ? $e->getMessage() : null
            ], $statusCode);
        }
    }

    public function notification(Request $request)
    {
        try {
            $notification = $request->all();
            Log::info('Midtrans notification received (PRODUCTION)', $notification);

            $orderId = $notification['order_id'] ?? null;
            $statusCode = $notification['status_code'] ?? null;
            $grossAmount = $notification['gross_amount'] ?? null;
            $signatureKey = $notification['signature_key'] ?? null;
            $transactionStatus = $notification['transaction_status'] ?? null;
            $fraudStatus = $notification['fraud_status'] ?? null;
            $paymentType = $notification['payment_type'] ?? null;
            $transactionTime = $notification['transaction_time'] ?? null;

            // Verify signature
            $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);

            if ($expectedSignature !== $signatureKey) {
                Log::error('Invalid signature from Midtrans');
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid signature'
                ], 403);
            }

            Log::info("Payment status: {$transactionStatus} for order_id: {$orderId}");

            // Extract log_id from order_id
            preg_match('/ORDER-\d+-(\d+)$/', $orderId, $matches);
            $logId = $matches[1] ?? null;

            if (!$logId) {
                Log::error('Cannot extract log_id from order_id', ['order_id' => $orderId]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid order_id format'
                ]);
            }

            // Determine new status
            $newStatus = 'belum beli';
            $updateData = [
                'order_id' => $orderId,
                'payment_type' => $paymentType,
            ];

            switch ($transactionStatus) {
                case 'capture':
                    if ($fraudStatus === 'challenge') {
                        $newStatus = 'challenge';
                    } elseif ($fraudStatus === 'accept') {
                        $newStatus = 'beli';
                        $updateData['paid_at'] = $transactionTime;
                    }
                    break;
                case 'settlement':
                    $newStatus = 'beli';
                    $updateData['paid_at'] = $transactionTime;
                    break;
                case 'cancel':
                case 'deny':
                case 'expire':
                    $newStatus = 'gagal';
                    $updateData['failure_reason'] = $transactionStatus;
                    break;
                case 'pending':
                    $newStatus = 'pending';
                    break;
            }

            // Update database
            $log = ReferralLog::find($logId);
            if ($log) {
                $log->update(array_merge($updateData, ['status' => $newStatus]));
                Log::info('Log status updated successfully', [
                    'log_id' => $logId,
                    'status' => $newStatus
                ]);
            } else {
                Log::warning('Log not found for update', ['log_id' => $logId]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification processed',
                'log_id' => $logId,
                'status' => $newStatus
            ]);

        } catch (\Exception $e) {
            Log::error('Notification processing error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Notification error but acknowledged'
            ]);
        }
    }

    public function transactionStatus($orderId)
    {
        if (empty($orderId)) {
            return response()->json([
                'success' => false,
                'message' => 'Order ID diperlukan'
            ], 400);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->getAuthHeader(),
                'Accept' => 'application/json'
            ])->timeout(15)->get("{$this->baseUrl}/{$orderId}/status");

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengecek status transaksi',
                    'error' => $response->json()
                ], $response->status());
            }

        } catch (\Exception $e) {
            Log::error('Check status error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengecek status transaksi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function cancelTransaction(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string'
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->getAuthHeader(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->timeout(15)->post("{$this->baseUrl}/{$request->order_id}/cancel");

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transaksi berhasil dibatalkan',
                    'data' => $response->json()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membatalkan transaksi',
                    'error' => $response->json()
                ], $response->status());
            }

        } catch (\Exception $e) {
            Log::error('Cancel transaction error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan transaksi',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}