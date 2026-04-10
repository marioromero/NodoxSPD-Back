<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Billing\PlanController;
use App\Http\Controllers\Billing\SubscriptionController;
use App\Http\Controllers\Billing\PaymentController;

/*
|--------------------------------------------------------------------------
| 1. Rutas Públicas
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/plans', [PlanController::class, 'index']); // Catálogo de planes

/*
|--------------------------------------------------------------------------
| 2. Rutas Protegidas (Cualquier usuario logueado)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Perfil y Logout (Tanto Empresas como Personas usan esto)
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    /*
    |----------------------------------------------------------------------
    | 3. Módulo de Empresa (Solo Admins de Empresa)
    |----------------------------------------------------------------------
    */
    Route::middleware('role:company_admin')->prefix('company')->group(function () {

        // Facturación
        Route::get('/billing/subscription', [SubscriptionController::class, 'current']);
        Route::post('/billing/subscribe', [SubscriptionController::class, 'store']);
        Route::get('/billing/payments', [PaymentController::class, 'index']);

        // Futuras rutas de la empresa:
        // Route::post('/legal-settings', [LegalController::class, 'update']);
    });

    /*
    |----------------------------------------------------------------------
    | 4. Módulo de Cliente Final (Solo Personas)
    |----------------------------------------------------------------------
    */
    Route::middleware('role:person')->prefix('portal')->group(function () {

        // Futuras rutas de la ticketera:
        // Route::get('/tickets', [TicketController::class, 'index']);
        // Route::post('/tickets', [TicketController::class, 'store']);
    });

});
