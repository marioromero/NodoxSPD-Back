<?php

namespace App\Http\Controllers\Api\Widget;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyPolicy;
use App\Services\Consent\WizardPurposeResolverService;
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
     * 5. Resuelve los propósitos activos dinámicamente via WizardPurposeResolverService.
     * 6. Extrae los proveedores desde wizard_data de la política.
     * 7. Construye la respuesta JSON con propósitos, configuración del widget y URL de política.
     * 8. Aplica headers de caché para CDN (Cache-Control, ETag, Vary).
     */
    public function show(Request $request, string $publicUuid, WizardPurposeResolverService $purposeResolver): JsonResponse
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

        // 5. Resolver los propósitos activos dinámicamente desde el wizard de la política.
        //    Conecta el Módulo 2 (catálogo de fines legales) con el Módulo 1 (Trust Widget).
        $activePurposes = $purposeResolver->resolve($policy);

        // 6. Construir el nodo de providers desde wizard_data de la política (no desde config estático).
        //    Cada categoría lee su lista de proveedores seleccionados en el wizard.
        $providers = [
            'analytics' => $this->extractProviders($policy->wizard_data, 'step_2_analytics'),
            'marketing' => $this->extractProviders($policy->wizard_data, 'step_3_marketing'),
            'personalization' => $this->extractProviders($policy->wizard_data, 'step_4_functionality'),
        ];

        // 7. Ensamblar la respuesta final. Regla estricta: sin IDs internos, sin datos de empresa.
        //    Los propósitos se resuelven dinámicamente desde el wizard via WizardPurposeResolverService.
        //    La empresa puede sobrescribir label y description en widget_config, pero required,
        //    default y legal_basis son inmutables (provienen del catálogo ConsentPurpose).
        $widgetConfig = $company->widget_config ?? [];
        $response = [
            'policy_hash' => $policy->integrity_hash,
            'policy_version' => $policy->company_version,
            'widget_config' => [
                'position' => $widgetConfig['position'] ?? 'bottom-left',
                'primary_color' => $widgetConfig['primary_color'] ?? '#1a73e8',
                'show_reject_all' => $widgetConfig['show_reject_all'] ?? true,
                'cookie_duration_days' => $widgetConfig['cookie_duration_days'] ?? 365,
                'providers' => $providers,
            ],
            'purposes' => $activePurposes->mapWithKeys(function ($purpose) use ($widgetConfig) {
                $custom = $widgetConfig['purposes'][$purpose->slug] ?? [];

                return [
                    $purpose->slug => [
                        'label' => $custom['label'] ?? $purpose->label,
                        'description' => $custom['description'] ?? $purpose->description,
                        'required' => ! $purpose->requires_consent,
                        'default' => $purpose->default_value,
                        'legal_basis' => $purpose->legal_basis,
                    ],
                ];
            })->toArray(),
            'legal_texts' => [
                'banner_title' => $widgetConfig['legal_texts']['banner_title'] ?? config('trust_widget.default_legal_texts.banner_title'),
                'banner_body' => $widgetConfig['legal_texts']['banner_body'] ?? config('trust_widget.default_legal_texts.banner_body'),
                'policy_url' => "https://cdn.tudominio.com/policies/{$policy->integrity_hash}.html",
            ],
        ];

        // 8. Aplicar headers de caché para CDN (Cloudflare).
        //    ETag basado en uuid + hash de política para invalidación automática.
        //    Sin Set-Cookie: rompe el caché de Cloudflare (must-revalidate por cookie de sesión).
        return response()->json($response, 200, [
            'Cache-Control' => 'public, max-age=3600, s-maxage=3600, stale-while-revalidate=86400',
            'Vary' => 'Accept-Encoding',
            'ETag' => "{$publicUuid}-{$policy->integrity_hash}",
        ])->withoutCookie('session')->withoutCookie('XSRF-TOKEN');
    }

    /**
     * Extrae la lista de proveedores desde wizard_data para un prefijo de paso dado.
     *
     * Combina el array de proveedores seleccionados ({prefix}_providers) con el
     * valor de proveedor custom ({prefix}_other_provider) si existe y no está vacío.
     * Retorna un array vacío si no hay proveedores configurados.
     *
     * @param  array  $wizardData  Respuestas del wizard almacenadas en la política.
     * @param  string  $prefix  Prefijo del paso (ej: step_2_analytics, step_3_marketing).
     * @return array<int, string> Lista de proveedores activos.
     */
    private function extractProviders(array $wizardData, string $prefix): array
    {
        $providers = $wizardData["{$prefix}_providers"] ?? [];

        if (! is_array($providers)) {
            $providers = [];
        }

        $other = $wizardData["{$prefix}_other_provider"] ?? null;

        if (! empty($other)) {
            $providers[] = $other;
        }

        return $providers;
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
