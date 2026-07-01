<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware CORS dinámico para el Trust Widget.
 *
 * Resuelve los dominios permitidos de cada empresa desde la BD (con cache de 12h)
 * y valida que el Origin del request coincida exactamente o sea un subdominio.
 * Esto reemplaza la configuración CORS estática de config/cors.php para rutas
 * del widget, permitiendo que cada empresa tenga su propia lista de dominios.
 */
class DynamicCorsMiddleware
{
    /** @var int TTL del cache de dominios permitidos (12 horas en segundos). */
    private const CACHE_TTL = 43200;

    /** @var int Tiempo que el navegador puede cachear la respuesta preflight (24 horas). */
    private const MAX_AGE = 86400;

    /**
     * Flujo principal del middleware:
     * 1. Si no hay header Origin, continúa sin agregar headers CORS.
     * 2. Si no se provee company_public_uuid, aborta con HTTP 400.
     * 3. Resuelve allowed_domains desde cache o BD.
     * 4. Valida el host del Origin contra la lista de dominios permitidos.
     * 5. Si es preflight OPTIONS permitido, retorna 204 con headers CORS.
     * 6. Si es GET/POST permitido, continúa el pipeline y adjunta headers CORS.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->headers->get('Origin');

        // Sin header Origin: no es una petición cross-origin, continúa sin CORS.
        if (! $origin) {
            return $next($request);
        }

        // Resuelve el UUID de la empresa desde route param o body.
        $companyPublicUuid = $request->route('company_public_uuid')
            ?? $request->input('company_public_uuid');

        if (! $companyPublicUuid) {
            return $this->badRequest();
        }

        // Cachea los dominios permitidos por 12 horas para evitar consultar la BD en cada petición.
        $allowedDomains = Cache::remember(
            "company_domains:{$companyPublicUuid}",
            self::CACHE_TTL,
            fn () => Company::where('public_uuid', $companyPublicUuid)->value('allowed_domains'),
        );

        $host = parse_url($origin, PHP_URL_HOST);

        // Valida si el host del Origin está en la lista de dominios permitidos.
        if (! $this->isOriginAllowed($host, $allowedDomains ?? [])) {
            return $this->denyOrigin($request);
        }

        $corsHeaders = $this->buildCorsHeaders($origin);

        // Preflight permitido: retorna 204 sin body, no continúa el pipeline.
        if ($request->isMethod('OPTIONS')) {
            return response('', 204)->withHeaders($corsHeaders);
        }

        // GET/POST permitido: continúa el pipeline y adjunta headers CORS a la respuesta.
        $response = $next($request);
        $response->headers->add($corsHeaders);

        return $response;
    }

    /**
     * Valida si el host del Origin coincide exactamente con un dominio permitido
     * o si es un subdominio válido (ej: blog.empresa.cl coincide con empresa.cl).
     */
    private function isOriginAllowed(string $host, array $allowedDomains): bool
    {
        foreach ($allowedDomains as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Construye el conjunto de headers CORS estándar para el Trust Widget.
     * El Origin se refleja exactamente (no se usa wildcard por seguridad).
     */
    private function buildCorsHeaders(string $origin): array
    {
        return [
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, X-Requested-With',
            'Access-Control-Max-Age' => (string) self::MAX_AGE,
            'Vary' => 'Origin',
        ];
    }

    /**
     * Deniega el origen no permitido.
     * Preflight OPTIONS: retorna 403 sin body (no expone información).
     * Resto: retorna JSON 403 con error genérico.
     */
    private function denyOrigin(Request $request): Response
    {
        if ($request->isMethod('OPTIONS')) {
            return response('', 403);
        }

        return response()->json([
            'status' => false,
            'message' => 'origin_not_allowed',
            'data' => null,
        ], 403);
    }

    /**
     * Respuesta 400 cuando no se puede identificar la empresa (sin company_public_uuid).
     */
    private function badRequest(): Response
    {
        return response()->json([
            'status' => false,
            'message' => 'Identificador de empresa requerido.',
            'data' => null,
        ], 400);
    }
}
