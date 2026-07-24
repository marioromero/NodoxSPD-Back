<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ConfirmPortalConsentRequest;
use App\Models\ConsentLog;
use App\Models\ConsentPurpose;
use App\Models\PendingConsent;
use App\Services\Consent\ProofHashService;
use App\Services\Consent\WizardPurposeResolverService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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
     * Procesa la firma del destinatario: crea el registro inmutable en
     * consent_logs y marca el token como confirmado.
     *
     * Flujo:
     * 1. Valida el token (mismas reglas que show: 404/410/409).
     * 2. Resuelve los propósitos activos via WizardPurposeResolverService.
     * 3. Fuerza required=true en fines legalmente obligatorios (requires_consent=false).
     * 4. Construye el proof_hash via ProofHashService (canonización + SHA-256).
     * 5. Inserta el ConsentLog inmutable (capture_method=live_portal).
     * 6. Marca el PendingConsent como confirmed + confirmed_at.
     * 7. Retorna 201 con proof_hash como recibo para el frontend.
     */
    public function confirm(
        ConfirmPortalConsentRequest $request,
        string $token,
        WizardPurposeResolverService $purposeResolver,
        ProofHashService $hashService,
    ): JsonResponse {
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
        $policy->load('template');

        $activePurposes = $purposeResolver->resolve($policy);

        $purposesSanitized = $this->enforceRequiredPurposes(
            $request->input('purposes', []),
            $activePurposes,
        );

        $email = $pendingConsent->decrypted_pii['email'] ?? null;
        $timestamp = now()->toIso8601String();

        $payload = $hashService->buildPayload(
            $email ?? $request->validated('visitor_uuid'),
            $policy->integrity_hash,
            $purposesSanitized,
            $timestamp,
        );

        $proofHash = $hashService->compute($payload);

        try {
            ConsentLog::create([
                'visitor_uuid' => $request->validated('visitor_uuid'),
                'identifier' => $email,
                'company_id' => $pendingConsent->company_id,
                'company_policy_id' => $policy->id,
                'purposes' => $purposesSanitized,
                'policy_hash' => $policy->integrity_hash,
                'proof_hash' => $proofHash,
                'consent_occurred_at' => now(),
                'capture_method' => 'live_portal',
                'ip_hash' => hash('sha256', $request->ip()),
                'user_agent' => substr($request->userAgent(), 0, 500),
            ]);
        } catch (QueryException $e) {
            Log::warning('PortalConsent: error al insertar consent_log', [
                'proof_hash' => $proofHash,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error al registrar el consentimiento.',
                'data' => null,
            ], 500);
        }

        $pendingConsent->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Documento firmado correctamente.',
            'data' => [
                'status' => 'confirmed',
                'proof_hash' => $proofHash,
            ],
        ], 201);
    }

    /**
     * Normaliza y enforcementa los purposes del request contra los fines activos.
     *
     * Reglas (espejo del WidgetConsentController):
     * - Fines con requires_consent=false → forzados a true (legalmente obligatorios).
     * - Demás fines activos → valor booleano del request, default false.
     * - Slugs del request no activos → ignorados silenciosamente.
     *
     * @param  array<string, mixed>  $requestPurposes  Purposes crudos del request.
     * @param  Collection<int, ConsentPurpose>  $activePurposes
     * @return array<string, bool> Purposes saneados.
     */
    private function enforceRequiredPurposes(array $requestPurposes, $activePurposes): array
    {
        $normalized = [];

        foreach ($activePurposes as $purpose) {
            if (! $purpose->requires_consent) {
                $normalized[$purpose->slug] = true;

                continue;
            }

            $normalized[$purpose->slug] = filter_var(
                $requestPurposes[$purpose->slug] ?? false,
                FILTER_VALIDATE_BOOLEAN,
            );
        }

        return $normalized;
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
