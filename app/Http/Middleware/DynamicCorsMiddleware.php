<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class DynamicCorsMiddleware
{
    private const CACHE_TTL = 43200; // 12 horas

    private const MAX_AGE = 86400;

    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->headers->get('Origin');

        if (! $origin) {
            return $next($request);
        }

        $companyPublicUuid = $request->route('company_public_uuid')
            ?? $request->input('company_public_uuid');

        if (! $companyPublicUuid) {
            return $this->badRequest();
        }

        $allowedDomains = Cache::remember(
            "company_domains:{$companyPublicUuid}",
            self::CACHE_TTL,
            fn () => Company::where('public_uuid', $companyPublicUuid)->value('allowed_domains'),
        );

        $host = parse_url($origin, PHP_URL_HOST);

        if (! $this->isOriginAllowed($host, $allowedDomains ?? [])) {
            return $this->denyOrigin($request);
        }

        $corsHeaders = $this->buildCorsHeaders($origin);

        if ($request->isMethod('OPTIONS')) {
            return response('', 204)->withHeaders($corsHeaders);
        }

        $response = $next($request);
        $response->headers->add($corsHeaders);

        return $response;
    }

    private function isOriginAllowed(string $host, array $allowedDomains): bool
    {
        foreach ($allowedDomains as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                return true;
            }
        }

        return false;
    }

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

    private function badRequest(): Response
    {
        return response()->json([
            'status' => false,
            'message' => 'Identificador de empresa requerido.',
            'data' => null,
        ], 400);
    }
}
