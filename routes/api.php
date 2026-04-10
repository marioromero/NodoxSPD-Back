<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Billing\PlanController;
use App\Http\Controllers\Billing\SubscriptionController;
use App\Http\Controllers\Billing\PaymentController;

// Catálogo de planes (Puede ser pública o requerir login según decidas, usualmente es pública)
Route::get('/plans', [PlanController::class, 'index']);

// Rutas protegidas de facturación
Route::middleware('auth:sanctum')->prefix('billing')->group(function () {
    Route::get('/subscription', [SubscriptionController::class, 'current']);
    Route::post('/subscribe', [SubscriptionController::class, 'store']);

    Route::get('/payments', [PaymentController::class, 'index']);
});
