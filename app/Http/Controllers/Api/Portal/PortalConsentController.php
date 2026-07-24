<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\PendingConsent;
use App\Services\Consent\WizardPurposeResolverService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador público del Portal Cautivo.
 *
 * Expone el endpoint que alimenta la interfaz Angular cuando un destinatario
 * hace clic en el enlace mágico de su correo transaccional. Valida el estado
 * del token y sirve la política junto con sus propósitos legales resueltos,
 * para que el frontend pueda renderizar el documento y capturar la firma.
 *
 * Seguridad clave:
 * - El token es criptográficamente aleatorio (64 chars hex), no enumerable.
 * - El PII del destinatario se descifra en memoria, nunca se loggea.
 * - Los propósitos se resuelven dinámicamente desde el wizard de la política,
 *   garantizando consistencia con el Trust Widget.
 */
class PortalConsentController extends Controller
{
    /**
     * Devuelve los datos de la política vinculada a un token de portal cautivo.
     *
     * Flujo:
     * 1. Busca el PendingConsent por token con relaciones company y companyPolicy.
     * 2. 404 si el token no existe.
     * 3. 410 Gone si el token expiró (actualiza status a expired en BD).
     * 4. 409 Conflict si el token ya fue confirmado.
     * 5. Resuelve los propósitos legales via WizardPurposeResolverService.
     * 6. Retorna JSON limpio con company, policy, recipient y purposes.
     */
    public function show(string $token, WizardPurposeResolverService $purposeResolver): JsonResponse
    {
        $pendingConsent = PendingConsent::with(['company', 'companyPolicy'])
            ->where('token', $token)
            ->first();

        if (! $pendingConsent) {
            return response()->json([
                'status' => false,
                'message' => 'El enlace no es válido o no existe.',
                'data' => null,
            ], 404);
        }

        if ($pendingConsent->status === 'confirmed') {
            return response()->json([
                'status' => false,
                'message' => 'Este documento ya fue firmado previamente.',
                'data' => null,
            ], 409);
        }

        if ($pendingConsent->expires_at < now()) {
            if ($pendingConsent->status === 'pending') {
                $pendingConsent->update(['status' => 'expired']);
            }

            return response()->json([
                'status' => false,
                'message' => 'El enlace ha expirado. Solicita uno nuevo.',
                'data' => null,
            ], 410);
        }

        $policy = $pendingConsent->companyPolicy;
        $company = $pendingConsent->company;
        $widgetConfig = $company->widget_config ?? [];

        $activePurposes = $purposeResolver->resolve($policy);

        $response = [
            'company' => [
                'business_name' => $company->business_name,
                'logo_url' => $widgetConfig['logo_url'] ?? null,
            ],
            'policy' => [
                'name' => $this->formatPolicyName($policy->document_type),
                'policy_hash' => $policy->integrity_hash,
                'url' => url('/api/public/policies/'.$policy->integrity_hash),
            ],
            'recipient' => [
                'email' => $pendingConsent->decrypted_pii['email'] ?? null,
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
        ];

        return response()->json($response, 200);
    }

    /**
     * Convierte el document_type técnico a un nombre legible para el frontend.
     *
     * @param  string  $documentType  Tipo técnico (ej: cookie_policy, workers_policy).
     * @return string Nombre legible (ej: "Política de Cookies").
     */
    private function formatPolicyName(string $documentType): string
    {
        return match ($documentType) {
            'cookie_policy' => 'Política de Cookies',
            'privacy_policy' => 'Política de Privacidad',
            'workers_policy' => 'Política de Trabajadores',
            default => 'Documento Legal',
        };
    }
}
