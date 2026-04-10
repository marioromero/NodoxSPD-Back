<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Billing\PlanController;
use App\Http\Controllers\Billing\SubscriptionController;
use App\Http\Controllers\Billing\PaymentController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
// Catálogo de planes (Puede ser pública o requerir login según decidas, usualmente es pública)
Route::get('/plans', [PlanController::class, 'index']);

// Rutas protegidas de facturación
Route::middleware('auth:sanctum')->prefix('billing')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/subscription', [SubscriptionController::class, 'current']);
    Route::post('/subscribe', [SubscriptionController::class, 'store']);

    Route::get('/payments', [PaymentController::class, 'index']);
});
