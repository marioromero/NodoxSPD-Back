<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\AuthenticationException;
use App\Http\Middleware\ForceJsonResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // 1. Forzar JSON en toda la aplicación
        $middleware->prepend(ForceJsonResponse::class);

        // 2. Habilitar sesiones para el panel Admin (Sanctum)
        $middleware->statefulApi();

        // 3. Alias básicos del sistema de autenticación de Laravel
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // Forzamos a que las excepciones siempre intenten renderizarse como JSON
        $exceptions->shouldRenderJsonWhen(fn () => true);

        // Capturar el error 404 (El error que acabas de tener)
        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
            return response()->json([
                'status' => false,
                'message' => 'El endpoint o recurso solicitado no existe.',
                'data' => null
            ], 404);
        });

        // Capturar intentos de acceso sin estar logueado (401)
        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'status' => false,
                'message' => 'No autorizado. Se requiere iniciar sesión.',
                'data' => null
            ], 401);
        });

        // Capturar errores generales del servidor (500)
        // Ocultamos el trace si estamos en producción por seguridad
        $exceptions->renderable(function (\Throwable $e, Request $request) {
            $isProduction = config('app.env') === 'production';

            return response()->json([
                'status' => false,
                'message' => $isProduction ? 'Error interno del servidor.' : $e->getMessage(),
                'data' => $isProduction ? null : [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        });

    })->create();
