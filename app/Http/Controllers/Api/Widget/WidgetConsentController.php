<?php

namespace App\Http\Controllers\Api\Widget;

use App\Http\Controllers\Controller;
use App\Http\Requests\Widget\StoreConsentRequest;
use App\Models\Company;
use App\Models\CompanyPolicy;
use App\Models\ConsentLog;
use App\Models\ConsentPurpose;
use App\Services\Consent\ProofHashService;
use App\Services\Consent\WizardPurposeResolverService;
use App\Traits\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Controlador público para la recepción de consentimientos desde el Trust Widget.
 *
 * Este endpoint es invocado por el widget embebido en los sitios de las empresas
 * cuando un visitante acepta, rechaza o configura sus preferencias de cookies.
 *
 * Seguridad clave:
 * - La empresa se resuelve SIEMPRE por public_uuid (nunca por ID numérico).
 * - El proof_hash es determinista (SHA-256 del payload canonizado), lo que
 *   garantiza idempotencia: el mismo consentimiento siempre produce el mismo hash.
 * - necessary_technical se fuerza a true en el backend (inmutable, no confiable
 *   desde el cliente).
 * - Los fines no activos para la empresa se ignoran silenciosamente.
 */
class WidgetConsentController extends Controller
{
    use ApiResponse;

    /**
     * Recibe, valida y persiste un consentimiento desde el Trust Widget.
     *
     * Flujo:
     * 1. Resuelve la empresa por public_uuid y su política de cookies activa.
     * 2. Resuelve los fines activos via WizardPurposeResolverService.
     * 3. Normaliza los purposes: fuerza necessary_technical=true, default false
     *    para los demás, ignora slugs no activos.
     * 4. Construye el payload canonizado y calcula el proof_hash (SHA-256).
     * 5. Inserta el registro inmutable en consent_logs.
     * 6. Si el proof_hash ya existe (idempotencia), retorna 200 ya registrado.
     * 7. Si es exitoso, retorna 201 con el proof_hash.
     */
    public function store(
        StoreConsentRequest $request,
        ProofHashService $hashService,
        WizardPurposeResolverService $purposeResolver,
    ): JsonResponse {
        $company = Company::where('public_uuid', $request->input('company_public_uuid'))->firstOrFail();

        $policy = CompanyPolicy::where('company_id', $company->id)
            ->where('document_type', 'cookie_policy')
            ->where('status', 'published')
            ->latest('published_at')
            ->first();

        if (! $policy) {
            return $this->error('Esta empresa no tiene una política de cookies publicada.', null, 404);
        }

        if ($policy->document_type === 'workers_policy') {
            return $this->error('No se pueden registrar consentimientos de políticas laborales desde el widget.', null, 403);
        }

        $policy->load('template');

        $activePurposes = $purposeResolver->resolve($policy);

        $purposesToSave = $this->normalizePurposes(
            $request->input('purposes', []),
            $activePurposes,
        );

        $payload = $hashService->buildPayload(
            $request->input('visitor_uuid'),
            $policy->integrity_hash,
            $purposesToSave,
            $request->input('timestamp'),
        );

        $proofHash = $hashService->compute($payload);

        try {
            ConsentLog::create([
                'visitor_uuid' => $request->input('visitor_uuid'),
                'identifier' => $request->input('visitor_uuid'),
                'company_id' => $company->id,
                'company_policy_id' => $policy->id,
                'purposes' => $purposesToSave,
                'policy_hash' => $policy->integrity_hash,
                'proof_hash' => $proofHash,
                'consent_occurred_at' => $request->input('timestamp'),
                'capture_method' => 'live_widget',
                'ip_hash' => $this->hashIp($request->ip()),
                'user_agent' => substr($request->userAgent(), 0, 500),
            ]);
        } catch (QueryException $e) {
            if ($e->errorInfo[1] ?? null === 1062) {
                return $this->success('Consentimiento ya registrado previamente.', ['status' => 'already_recorded'], 200);
            }

            Log::warning('WidgetConsent: error al insertar consent_log', [
                'proof_hash' => $proofHash,
                'error' => $e->getMessage(),
            ]);

            return $this->error('Error al registrar el consentimiento.', null, 500);
        }

        return $this->success(
            'Consentimiento registrado correctamente.',
            ['status' => 'recorded', 'proof_hash' => $proofHash],
            201,
        );
    }

    /**
     * Normaliza el array de purposes del request contra los fines activos.
     *
     * Reglas:
     * - necessary_technical se fuerza a true (inmutable por backend).
     * - Para los demás fines activos, se usa el valor booleano del request.
     *   Si no viene en el request, se asume false (privacidad por defecto).
     * - Los slugs del request que no sean fines activos se ignoran silenciosamente.
     *
     * @param  array<string, mixed>  $requestPurposes  Purposes crudos del request.
     * @param  Collection<int, ConsentPurpose>  $activePurposes
     * @return array<string, bool> Purposes normalizados, llave = slug, valor = bool.
     */
    private function normalizePurposes(array $requestPurposes, $activePurposes): array
    {
        $normalized = [];

        foreach ($activePurposes as $purpose) {
            if ($purpose->slug === 'necessary_technical') {
                $normalized['necessary_technical'] = true;

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
     * Hashea la IP del visitante con SHA-256 para auditoría forense.
     * No se almacena la IP en crudo (privacidad por diseño).
     */
    private function hashIp(?string $ip): ?string
    {
        if (! $ip) {
            return null;
        }

        return hash('sha256', $ip);
    }
}
