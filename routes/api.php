<?php
// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\MidtransController;
use App\Http\Controllers\LogController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

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
Route::prefix('midtrans')->group(function () {
    Route::post('create-transaction', [MidtransController::class, 'createTransaction']);
    Route::post('notification', [MidtransController::class, 'notification']);
    Route::get('transaction-status/{order_id}', [MidtransController::class, 'transactionStatus']);
    Route::post('cancel-transaction', [MidtransController::class, 'cancelTransaction']);
});

// Log Routes
Route::post('log-click', [LogController::class, 'store']);
Route::put('log-click/{id}/order-id', [LogController::class, 'updateOrderId']);
Route::get('logs', [LogController::class, 'index']);
Route::patch('logs/{id}', [LogController::class, 'update']);