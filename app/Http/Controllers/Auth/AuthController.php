<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Registro de usuarios (Transaccional)
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            // DB::transaction asegura que si falla la creación del perfil, no se cree el usuario (o viceversa)
            $user = DB::transaction(function () use ($request) {

                // 1. Crear el núcleo del usuario (Preparado para el futuro con Google)
                $user = User::create([
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'type' => $request->type,
                ]);

                // 2. Crear el perfil correspondiente según el tipo
                if ($request->type === 'company') {
                    $user->company()->create([
                        'public_uuid' => Str::uuid(), // Generamos el ID para el Iframe
                        'business_name' => $request->business_name,
                    ]);

                    //  asignarle el rol de Spatie:
                    $user->assignRole('company_admin');
                } else {
                    $user->persona()->create([
                        'first_name' => $request->first_name,
                        'last_name' => $request->last_name,
                    ]);
                }

                return $user;
            });

            // TODO: Aquí dispararemos el evento de envío de correo de verificación después.

            return $this->success('Registro completado exitosamente. Por favor, verifica tu correo.', null, 201);

        } catch (\Exception $e) {
            Log::error('Error en registro', [
                'email' => $request->email,
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);

            return $this->error('Ocurrió un error al registrar el usuario.', null, 500);
        }
    }

    /**
     * Inicio de sesión (Sesiones por Cookie de Sanctum)
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // 1. Intentamos autenticar con las credenciales dadas
        if (! Auth::attempt($request->only('email', 'password'))) {
            return $this->error('Credenciales incorrectas.', null, 401);
        }

        $user = Auth::user();

        // 2. Verificamos que el usuario esté activo en el sistema
        if (! $user->is_active) {
            Auth::logout();

            return $this->error('Esta cuenta ha sido suspendida.', null, 403);
        }

        // 3. Verificamos que el rol elegido en el frontend coincida con el real
        if ($user->type !== $request->type) {
            Auth::logout();

            return $this->error('Tipo de cuenta incorrecto. Intenta iniciar sesión en el portal adecuado.', null, 403);
        }

        // 4. Prevenir ataques de fijación de sesión (Regla de oro en Laravel)
        $request->session()->regenerate();

        // 5. Cargar la relación correspondiente para enviarla al Frontend
        $user->load($user->type === 'company' ? 'company' : 'persona');

        return $this->success('Inicio de sesión exitoso.', $user);
    }

    /**
     * Obtener el usuario actual (Para que Angular sepa quién está logueado al recargar la página)
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load($user->type === 'company' ? 'company' : 'persona');

        return $this->success('Datos del usuario actual', $user);
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        // Invalidar la sesión y destruir el token CSRF para evitar robos posteriores
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->success('Sesión cerrada correctamente.');
    }
}
