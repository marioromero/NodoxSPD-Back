<?php

use App\Http\Middleware\CheckCompanyOnboarding;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
            'auth' => Authenticate::class,
            'verified' => EnsureEmailIsVerified::class,
            // Spatie
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'onboarding.check' => CheckCompanyOnboarding::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->shouldRenderJsonWhen(fn () => true);

        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
            Log::warning('404 Not Found', [
                'path' => $request->path(),
                'method' => $request->method(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'El endpoint o recurso solicitado no existe.',
                'data' => null,
            ], 404);
        });

        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
            Log::warning('401 Unauthorized', [
                'path' => $request->path(),
                'method' => $request->method(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'No autorizado. Se requiere iniciar sesión.',
                'data' => null,
            ], 401);
        });

        $exceptions->renderable(function (AuthorizationException $e, Request $request) {
            Log::warning('403 Forbidden', [
                'path' => $request->path(),
                'method' => $request->method(),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'No tienes permisos para realizar esta acción.',
                'data' => null,
            ], 403);
        });

        $exceptions->renderable(function (ValidationException $e, Request $request) {
            Log::info('422 Validation failed', [
                'path' => $request->path(),
                'method' => $request->method(),
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'data' => [
                    'errors' => $e->errors(),
                ],
            ], 422);
        });

        $exceptions->renderable(function (Throwable $e, Request $request) {
            Log::error('500 Internal Server Error', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'path' => $request->path(),
                'method' => $request->method(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);

            $isProduction = app()->isProduction();

            return response()->json([
                'status' => false,
                'message' => $isProduction ? 'Error interno del servidor.' : $e->getMessage(),
                'data' => $isProduction ? null : [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ], 500);
        });

    })->create();
