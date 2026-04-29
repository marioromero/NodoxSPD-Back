<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Billing\PaymentController;
use App\Http\Controllers\Billing\PlanController;
use App\Http\Controllers\Billing\SubscriptionController;
use App\Http\Controllers\BusinessActivityController;
use App\Http\Controllers\Company\OnboardingController;
use Illuminate\Support\Facades\Route;

// 1. Rutas Públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/plans', [PlanController::class, 'index']); // Catálogo de planes

// 2. Rutas Protegidas (Cualquier usuario autenticado)
Route::middleware('auth:sanctum')->group(function () {

    // Perfil y Logout (Compartido entre Empresas y Personas)
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // 3. Módulo de Empresa (Solo Admins de Empresa)
    Route::middleware('role:company_admin')->prefix('company')->group(function () {

        // Obtener todos los giros/actividades
        Route::get('/activities', [BusinessActivityController::class, 'index']);

        // Endpoint para completar el onboarding (DEBE estar fuera de la validación)
        Route::post('/onboarding', [OnboardingController::class, 'complete']);

        // Obtener todos los sectores/rubros
        Route::get('/activities/sectors', [BusinessActivityController::class, 'getSectors']);

        // Obtener actividades por sector ID
        Route::get('/activities/sector/{sectorId}', [BusinessActivityController::class, 'filterBySector']);

        // Facturación (Fuera del bloqueo de onboarding para no frenar pagos)
        Route::prefix('billing')->group(function () {
            Route::get('/subscription', [SubscriptionController::class, 'current']);
            Route::post('/subscribe', [SubscriptionController::class, 'store']);
            Route::get('/payments', [PaymentController::class, 'index']);
        });

        // ZONA ESTRICTA: Módulos Core SaaS protegidos por la Ley 21.719
        Route::middleware('onboarding.check')->group(function () {

            // Módulo 1 y 2: Trust Widget y Políticas Legales
            // Route::post('/legal-settings', [LegalController::class, 'update']);

            // Módulo 3: Plataforma de Gestión ARCO+P (Lado Empresa)
            // Route::get('/arco-requests', [ArcoController::class, 'index']);

            // Módulo 4: Notificación de Brechas
            // Route::post('/security-breaches', [BreachController::class, 'store']);
        });
    });

    // 4. Módulo de Cliente Final (Solo Personas)
    Route::middleware('role:person')->prefix('portal')->group(function () {

        // Portal de solicitudes ARCO+P (Lado Ciudadano)
        // Route::get('/tickets', [TicketController::class, 'index']);
        // Route::post('/tickets', [TicketController::class, 'store']);
    });
});
