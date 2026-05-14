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
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Registro de usuarios (Transaccional)
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {

            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'type' => $request->type,
            ]);

            if ($request->type === 'company') {
                $user->company()->create([
                    'public_uuid' => Str::uuid(),
                    'business_name' => $request->business_name,
                ]);

                $user->assignRole('company_admin');
            } else {
                $user->persona()->create([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                ]);
            }

            return $user;
        });

        return $this->success('Registro completado exitosamente. Por favor, verifica tu correo.', null, 201);
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
