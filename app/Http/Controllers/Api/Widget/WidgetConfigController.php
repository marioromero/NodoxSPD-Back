<?php

namespace App\Http\Controllers\Api\Widget;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controlador público del Trust Widget.
 *
 * Expone la configuración del widget embebible de cada empresa de forma pública
 * (sin autenticación). La respuesta está optimizada para caché en CDN (Cloudflare):
 * headers Cache-Control, ETag y Vary, sin Set-Cookie.
 *
 * Seguridad clave:
 * - La empresa se resuelve SIEMPRE por public_uuid (nunca por ID numérico).
 * - La respuesta no expone ningún ID interno (id, company_id, user_id).
 * - Se filtra explícitamente document_type = 'workers_policy' del scope público.
 */
class WidgetConfigController extends Controller
{
    /**
     * Devuelve la configuración pública del widget para una empresa.
     *
     * Flujo:
     * 1. Valida que el parámetro sea un UUID v4 válido.
     * 2. Resuelve la empresa por public_uuid (firstOrFail → 404 si no existe).
     * 3. Obtiene la política de cookies publicada más reciente.
     * 4. Si no hay política publicada, retorna 404 con error específico.
     * 5. Construye la respuesta JSON con propósitos, configuración del widget y URL de política.
     * 6. Aplica headers de caché para CDN (Cache-Control, ETag, Vary).
     */
    public function show(Request $request, string $publicUuid): JsonResponse
    {
        // 1. Validar que el parámetro sea un UUID v4 válido.
        if (! $this->isValidUuidV4($publicUuid)) {
            return response()->json([
                'status' => false,
                'message' => 'El identificador proporcionado no es un UUID v4 válido.',
                'data' => null,
            ], 400);
        }

        // 2. Resolver la empresa explícitamente por public_uuid (nunca por ID numérico).
        $company = Company::where('public_uuid', $publicUuid)->firstOrFail();

        // 3. Obtener la política de cookies activa más reciente, excluyendo workers_policy
        //    por seguridad (documentos laborales no son públicos para el widget).
        $policy = CompanyPolicy::where('company_id', $company->id)
            ->where('document_type', 'cookie_policy')
            ->where('status', 'published')
            ->whereNotIn('document_type', ['workers_policy'])
            ->latest('published_at')
            ->first();

        // 4. Si no hay política publicada, retornar 404 con error específico.
        if (! $policy) {
            Log::info('Widget config: empresa sin política de cookies publicada', [
                'public_uuid' => $publicUuid,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Esta empresa no tiene una política de cookies publicada.',
                'data' => null,
            ], 404);
        }

        // 5. Ensamblar la respuesta final. Regla estricta: sin IDs internos, sin datos de empresa.
        $response = [
            'policy_hash' => $policy->integrity_hash,
            'policy_version' => $policy->company_version,
            'widget_config' => $company->widget_config ?? [
                'position' => 'bottom-left',
                'primary_color' => '#1a73e8',
                'show_reject_all' => true,
                'cookie_duration_days' => 365,
                'providers' => [
                    'analytics' => ['google_analytics', 'hotjar'],
                    'marketing' => ['meta_pixel', 'google_ads'],
                    'personalization' => ['intercom'],
                ],
            ],
            'purposes' => [
                'necessary' => ['label' => 'Técnicas / Necesarias', 'required' => true],
                'analytics' => ['label' => 'Analítica / Medición', 'required' => false],
                'marketing' => ['label' => 'Publicidad', 'required' => false],
                'personalization' => ['label' => 'Funcionalidad', 'required' => false],
            ],
            'legal_texts' => [
                'banner_title' => 'Este sitio usa cookies',
                'banner_body' => 'Usamos cookies para...',
                'policy_url' => "https://cdn.tudominio.com/policies/{$policy->integrity_hash}.html",
            ],
        ];

        // 6. Aplicar headers de caché para CDN (Cloudflare).
        //    ETag basado en uuid + hash de política para invalidación automática.
        //    Sin Set-Cookie: rompe el caché de Cloudflare (must-revalidate por cookie de sesión).
        return response()->json($response, 200, [
            'Cache-Control' => 'public, max-age=3600, s-maxage=3600, stale-while-revalidate=86400',
            'Vary' => 'Accept-Encoding',
            'ETag' => "{$publicUuid}-{$policy->integrity_hash}",
        ])->withoutCookie('session')->withoutCookie('XSRF-TOKEN');
    }

    /**
     * Valida que un string sea un UUID v4 válido (formato RFC 4122).
     * Patrón: 8-4-4-4-12 hex dígitos, con la versión 4 en el tercer grupo.
     */
    private function isValidUuidV4(string $uuid): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid,
        );
    }
}
