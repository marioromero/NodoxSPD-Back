<?php

use App\Http\Middleware\CheckCompanyOnboarding;
use App\Http\Middleware\DynamicCorsMiddleware;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Parsea un mensaje de error 1062 de MariaDB/MySQL extrayendo el campo
 * duplicado y devolviendo un mensaje legible.
 */
function parseDuplicateError(string $errorMessage): string
{
    // "Duplicate entry 'VALOR' for key 'tabla_campo_unique'"
    if (preg_match("/Duplicate entry '(.+)' for key '(.+)'/", $errorMessage, $m)) {
        $value = $m[1];
        $key = $m[2];

        $fieldMap = [
            'companies_tax_id_unique' => 'RUT',
            'companies_business_name_unique' => 'Razón social',
            'users_email_unique' => 'Correo electrónico',
        ];

        $field = $fieldMap[$key] ?? "el campo «{$key}»";

        return "El valor «{$value}» ya está registrado en el sistema para {$field}.";
    }

    return 'El recurso ya existe (valor duplicado).';
}

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
            'cors.dynamic' => DynamicCorsMiddleware::class,
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

        $exceptions->renderable(function (QueryException $e, Request $request) {
            $sqlState = $e->errorInfo[0] ?? null;
            $driverCode = $e->errorInfo[1] ?? null;

            if ($sqlState === '23000') {
                $errors = [];

                if ($driverCode === 1062) {
                    $errors[] = parseDuplicateError($e->errorInfo[2] ?? '');
                } elseif ($driverCode === 1451) {
                    $errors[] = 'No se puede eliminar porque está siendo utilizado por otros registros.';
                } elseif ($driverCode === 1452) {
                    $errors[] = 'Referencia inválida. El recurso relacionado no existe.';
                } else {
                    $errors[] = 'Error de integridad de datos.';
                }

                Log::warning('DB Integrity constraint', [
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'sqlState' => $sqlState,
                    'driverCode' => $driverCode,
                    'message' => $e->getMessage(),
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Error de integridad de datos.',
                    'data' => [
                        'errors' => $errors,
                    ],
                ], 409);
            }

            Log::error('DB Query error', [
                'path' => $request->path(),
                'method' => $request->method(),
                'sqlState' => $sqlState,
                'driverCode' => $driverCode,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error en la base de datos.',
                'data' => null,
            ], 500);
        });

        $exceptions->renderable(function (LogicException $e, Request $request) {
            Log::warning('409 Conflict - Inmutabilidad/Régla de negocio', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'path' => $request->path(),
                'method' => $request->method(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'No se puede modificar ni eliminar este recurso.',
                'data' => null,
            ], 409);
        });

        $exceptions->renderable(function (Throwable $e, Request $request) {
            Log::error('500 Internal Server Error', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'path' => $request->path(),
                'method' => $request->method(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error interno del servidor.',
                'data' => null,
            ], 500);
        });

    })->create();
