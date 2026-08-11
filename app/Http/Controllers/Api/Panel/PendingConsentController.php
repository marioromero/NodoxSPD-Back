<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\SendPortalLinkRequest;
use App\Jobs\SendConsentLinkJob;
use App\Models\CompanyBatch;
use App\Models\CompanyPolicy;
use App\Models\PendingConsent;
use App\Traits\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

/**
 * Controlador del panel de administración para gestionar enlaces de firma.
 *
 * Arquitectura híbrida según volumen del envío:
 *
 * - Camino A (1 correo): ejecución síncrona. Pre-check de deduplicación,
 *   insert en BD, dispatch del Job. No usa lotes ni tabla puente.
 *   Respuesta inmediata con mode: "single".
 *
 * - Camino B (>1 correo): ejecución asíncrona via Bus::batch. Cero
 *   escrituras síncronas en BD — la creación y deduplicación se delega
 *   a cada Job individual. Cola priority (<=20) o bulk (>20).
 *   Respuesta 202 con mode: "batch" y batch_id para polling.
 *
 * Rate limit de negocio: máximo 3 lotes grandes (>20 correos) en 24h.
 *
 * NOTA DE ARQUITECTURA: Para >500 correos se requerirá notificación asíncrona
 * (webhook o polling diferido), no respuesta síncrona con batch_id. El lote
 * máximo de 500 está pensado para respuesta interactiva con polling del batch.
 */
class PendingConsentController extends Controller
{
    use ApiResponse;

    /**
     * Lista los enlaces de firma enviados por la empresa, con filtros y paginación.
     *
     * Filtros disponibles via query params:
     * - status: filtra por estado (pending, confirmed, expired, bounced).
     *
     * Orden: created_at DESC (más recientes primero).
     * Paginación: 15 registros por página.
     * Eager loading: companyPolicy para evitar N+1 y obtener el nombre del documento.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company->id;

        $query = PendingConsent::with('companyPolicy')
            ->where('company_id', $companyId)
            ->latest('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $pendingConsents = $query->paginate(15);

        $transformed = $pendingConsents->getCollection()->map(fn (PendingConsent $item): array => [
            'id' => $item->id,
            'email' => $item->decrypted_pii['email'] ?? null,
            'policy_name' => $this->formatPolicyName($item->companyPolicy->document_type ?? ''),
            'status' => $item->status,
            'created_at' => $item->created_at->toIso8601String(),
            'expires_at' => $item->expires_at->toIso8601String(),
            'confirmed_at' => $item->confirmed_at?->toIso8601String(),
        ]);

        return $this->success('Enlaces de firma enviados.', [
            'data' => $transformed,
            'pagination' => [
                'current_page' => $pendingConsents->currentPage(),
                'last_page' => $pendingConsents->lastPage(),
                'per_page' => $pendingConsents->perPage(),
                'total' => $pendingConsents->total(),
                'from' => $pendingConsents->firstItem(),
                'to' => $pendingConsents->lastItem(),
            ],
        ]);
    }

    /**
     * Despacha enlaces de firma del Portal Cautivo por correo.
     *
     * Camino A (1 correo): síncrono, pre-check + insert + dispatch.
     * Camino B (>1 correo): asíncrono, Bus::batch conJobs idempotentes.
     *
     * @param  SendPortalLinkRequest  $request  Request validada con emails[] y company_policy_id.
     */
    public function store(SendPortalLinkRequest $request): JsonResponse
    {
        $company = $request->user()->company;

        $policy = CompanyPolicy::where('id', $request->validated('company_policy_id'))
            ->where('company_id', $company->id)
            ->where('status', 'published')
            ->first();

        if (! $policy) {
            return $this->error(
                'La política seleccionada no pertenece a tu empresa o no está publicada.',
                null,
                403,
            );
        }

        $emails = $request->validated('emails');

        // Filtro de duplicados internos del request
        $uniqueEmails = array_values(array_unique($emails));

        // Camino A: envío único síncrono
        if (count($uniqueEmails) === 1) {
            return $this->dispatchSingle($company->id, $policy->id, $uniqueEmails[0]);
        }

        // Camino B: envío masivo asíncrono
        return $this->dispatchBatch($company, $policy, $uniqueEmails);
    }

    /**
     * Camino A — Envío único síncrono.
     *
     * Pre-check de deduplicación usando pii_hash + policy_id. Si ya existe
     * pendiente vigente, retorna status: "already_pending". Si no, crea el
     * registro PendingConsent, despacha el Job y retorna status: "created".
     *
     * No usa lotes ni tabla puente. El Job se despacha directamente a la
     * cola consent-emails-priority.
     *
     * @param  int  $companyId  ID de la empresa.
     * @param  int  $companyPolicyId  ID de la política.
     * @param  string  $email  Email del destinatario.
     */
    private function dispatchSingle(int $companyId, int $companyPolicyId, string $email): JsonResponse
    {
        $piiHash = hash('sha256', $email);

        $existing = PendingConsent::where('pii_hash', $piiHash)
            ->where('company_policy_id', $companyPolicyId)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            return response()->json([
                'status' => true,
                'message' => 'Ya existe un enlace pendiente vigente para este destinatario.',
                'data' => [
                    'mode' => 'single',
                    'status' => 'already_pending',
                    'message' => 'Enlace ya enviado previamente, aún vigente.',
                ],
            ], 200);
        }

        try {
            $pendingConsent = PendingConsent::create([
                'company_id' => $companyId,
                'company_policy_id' => $companyPolicyId,
                'token' => PendingConsent::generateToken(),
                'pii_payload' => ['email' => $email],
                'pii_hash' => $piiHash,
                'status' => 'pending',
                'source' => 'manual_panel',
                'expires_at' => now()->addDays(7),
            ]);
        } catch (QueryException $e) {
            $errorCode = $e->errorInfo[1] ?? null;

            if ($errorCode === 1062 || str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                return response()->json([
                    'status' => true,
                    'message' => 'Ya existe un enlace pendiente vigente para este destinatario.',
                    'data' => [
                        'mode' => 'single',
                        'status' => 'already_pending',
                        'message' => 'Enlace ya enviado previamente, aún vigente.',
                    ],
                ], 200);
            }

            throw $e;
        }

        SendConsentLinkJob::dispatch(
            companyId: $companyId,
            companyPolicyId: $companyPolicyId,
            email: $email,
            pendingUniquenessKey: $piiHash.':'.$companyPolicyId,
            pendingConsent: $pendingConsent,
        );

        return response()->json([
            'status' => true,
            'message' => 'Enlace enviado exitosamente.',
            'data' => [
                'mode' => 'single',
                'status' => 'created',
                'message' => 'Enlace enviado exitosamente.',
            ],
        ], 201);
    }

    /**
     * Camino B — Envío masivo asíncrono via Bus::batch.
     *
     * Cero escrituras síncronas en BD: cada Job maneja su propia creación
     * idempotente. Cola priority (<=20) o bulk (>20).
     *
     * Rate limit de negocio: máximo 3 lotes grandes (>20) en 24h.
     *
     * @param  mixed  $company  Modelo Company de la empresa.
     * @param  CompanyPolicy  $policy  Política publicada.
     * @param  array<int, string>  $emails  Emails únicos del request.
     */
    private function dispatchBatch(mixed $company, CompanyPolicy $policy, array $emails): JsonResponse
    {
        // Rate limit de negocio: máximo 3 lotes grandes (>20 correos) en 24h
        if (count($emails) > 20) {
            $recentLargeBatches = CompanyBatch::where('company_id', $company->id)
                ->where('created_at', '>=', now()->subDay())
                ->with('batch')
                ->get()
                ->filter(fn (CompanyBatch $cb): bool => $cb->batch && $cb->batch->totalJobs > 20)
                ->count();

            if ($recentLargeBatches >= 3) {
                return $this->error(
                    'Has alcanzado el límite de 3 envíos grandes (>20 correos) en las últimas 24 horas.',
                    null,
                    429,
                );
            }
        }

        // Selección de cola: priority para lotes pequeños, bulk para grandes
        $queueName = count($emails) > 20 ? 'consent-emails-bulk' : 'consent-emails-priority';

        // Construir array de Jobs con pendingUniquenessKey precalculada
        $jobs = [];
        foreach ($emails as $email) {
            $jobs[] = new SendConsentLinkJob(
                companyId: $company->id,
                companyPolicyId: $policy->id,
                email: $email,
                pendingUniquenessKey: hash('sha256', $email).':'.$policy->id,
            );
        }

        try {
            $batch = Bus::batch($jobs)
                ->onQueue($queueName)
                ->name("Consent links: {$company->business_name} -> {$policy->document_type}")
                ->dispatch();

            // Guardar relación company_batches para aislamiento multitenant
            CompanyBatch::create([
                'company_id' => $company->id,
                'batch_id' => $batch->id,
            ]);

        } catch (\Exception $e) {
            Log::warning('PendingConsent: error al despachar batch', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('Error al procesar el envío masivo.', null, 500);
        }

        return response()->json([
            'status' => true,
            'message' => "Lote de {$batch->totalJobs} correos despachado.",
            'data' => [
                'mode' => 'batch',
                'batch_id' => $batch->id,
                'total' => $batch->totalJobs,
                'status' => 'queued',
            ],
        ], 202);
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
            default => 'Documento sin nombre',
        };
    }
}
