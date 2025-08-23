<?php
// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\MidtransController;
use App\Http\Controllers\LogController;
use App\Http\Middleware\Cors; // <-- Pastikan ini ada

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ✅ Ini tetap diperlukan untuk menangani preflight request OPTIONS
Route::options('/{any}', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', 'https://islamic-it-school.com')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
        ->header('Access-Control-Allow-Credentials', 'true');
})->where('any', '.*');

// Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register-admin', [AuthController::class, 'register']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('admin-only', [AuthController::class, 'adminOnly']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

// Referral Routes
Route::prefix('referal')->group(function () {
    Route::get('/', [ReferralController::class, 'index']);
    Route::post('/', [ReferralController::class, 'store']);
    Route::delete('/{id}', [ReferralController::class, 'destroy']);
    Route::put('/use/{kode_referal}', [ReferralController::class, 'useReferral']);
});

// Midtrans/Payment Routes
// Menambahkan middleware CORS ke grup ini
Route::prefix('midtrans')->middleware(Cors::class)->group(function () {
    Route::post('create-transaction', [MidtransController::class, 'createTransaction']);
    Route::post('notification', [MidtransController::class, 'notification']);
    Route::get('transaction-status/{order_id}', [MidtransController::class, 'transactionStatus']);
    Route::post('cancel-transaction', [MidtransController::class, 'cancelTransaction']);
});

// Log Routes
// Menambahkan middleware CORS ke setiap rute yang tidak dalam grup
Route::post('log-click', [LogController::class, 'store'])->middleware(Cors::class);
Route::put('log-click/{id}/order-id', [LogController::class, 'updateOrderId'])->middleware(Cors::class);
Route::get('logs', [LogController::class, 'index'])->middleware(Cors::class);
Route::patch('logs/{id}', [LogController::class, 'update'])->middleware(Cors::class);