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
                'error' => 'invalid_uuid',
                'message' => 'El identificador proporcionado no es un UUID v4 válido.',
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
                'error' => 'no_active_policy',
                'message' => 'Esta empresa no tiene una política de cookies publicada.',
            ], 404);
        }

        // 5. Extraer la configuración del widget desde el JSON column de la empresa.
        $widgetConfig = $company->widget_config ?? [];

        // 6. Mapear los propósitos del consentimiento con sus labels y estado de obligatoriedad.
        //    'necessary' siempre es required: true (no se puede desactivar).
        $purposes = $this->buildPurposes($widgetConfig);

        // 7. Construir la URL pública de la política usando el integrity_hash (CDN-friendly).
        $policyUrl = url("/api/public/policies/{$policy->integrity_hash}");

        // 8. Ensamblar la respuesta final. Regla estricta: sin IDs internos.
        $response = [
            'company' => [
                'public_uuid' => $company->public_uuid,
                'business_name' => $company->business_name,
            ],
            'policy' => [
                'hash' => $policy->integrity_hash,
                'version' => $policy->company_version,
                'published_at' => $policy->published_at?->toIso8601String(),
                'url' => $policyUrl,
            ],
            'widget' => [
                'theme' => $widgetConfig['theme'] ?? 'light',
                'position' => $widgetConfig['position'] ?? 'bottom-right',
                'language' => $widgetConfig['language'] ?? 'es',
                'banner_text' => $widgetConfig['banner_text'] ?? null,
            ],
            'purposes' => $purposes,
        ];

        // 9. Aplicar headers de caché para CDN (Cloudflare).
        //    ETag basado en uuid + hash de política para invalidación automática.
        //    Sin Set-Cookie: rompe el caché de Cloudflare (must-revalidate por cookie de sesión).
        return response()->json($response, 200, [
            'Cache-Control' => 'public, max-age=3600, s-maxage=3600, stale-while-revalidate=86400',
            'Vary' => 'Accept-Encoding',
            'ETag' => "{$publicUuid}-{$policy->integrity_hash}",
        ])->withoutCookie('session')->withoutCookie('XSRF-TOKEN');
    }

    /**
     * Construye el array de propósitos del consentimiento con labels y estado required.
     *
     * 'necessary' es siempre required: true (normativa legal).
     * Los demás propósitos toman su configuración desde widget_config o defaults.
     *
     * @return array<int, array{key: string, label: string, required: bool}>
     */
    private function buildPurposes(array $widgetConfig): array
    {
        $config = $widgetConfig['purposes'] ?? [];

        return [
            [
                'key' => 'necessary',
                'label' => $config['necessary']['label'] ?? 'Necesarias',
                'required' => true,
            ],
            [
                'key' => 'analytics',
                'label' => $config['analytics']['label'] ?? 'Analíticas',
                'required' => $config['analytics']['required'] ?? false,
            ],
            [
                'key' => 'marketing',
                'label' => $config['marketing']['label'] ?? 'Marketing',
                'required' => $config['marketing']['required'] ?? false,
            ],
            [
                'key' => 'personalization',
                'label' => $config['personalization']['label'] ?? 'Personalización',
                'required' => $config['personalization']['required'] ?? false,
            ],
        ];
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
