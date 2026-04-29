<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCompanyOnboarding
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Verificamos si es una compañía y si la relación existe
        if ($user && $user->type === 'company' && $user->company) {

            // Si no ha completado el onboarding, bloqueamos el acceso
            if (! $user->company->hasCompletedOnboarding()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Falta información legal obligatoria para operar el sistema.',
                    'error_code' => 'ONBOARDING_REQUIRED',
                    'data' => null,
                ], 403);
            }
        }

        return $next($request);
    }
}
