<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Forzamos al framework a tratar la petición como si el cliente hubiera pedido JSON explícitamente
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
