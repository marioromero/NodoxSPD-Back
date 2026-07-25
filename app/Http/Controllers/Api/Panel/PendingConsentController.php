<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\SendPortalLinkRequest;
use App\Jobs\SendConsentLinkJob;
use App\Models\CompanyPolicy;
use App\Models\PendingConsent;
use App\Traits\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controlador del panel de administración para gestionar enlaces de firma.
 *
 * Perite a la Pyme enviar un enlace del Portal Cautivo a un trabajador o lead
 * por correo electrónico. Crea el registro PendingConsent con la PII cifrada
 * y despacha el Job en background que envía el correo transaccional.
 *
 * Seguridad clave:
 * - La política debe pertenecer a la empresa del usuario autenticado (403 si no).
 * - La política debe estar publicada (no se pueden enviar borradores).
 * - La PII del destinatario se cifra en el mutador del modelo (AES-256-CBC).
 * - El token es criptográficamente aleatorio (64 chars hex), no enumerable.
 * - Prevención de duplicados: usa pii_hash (SHA-256 del email, indexado) para
 *   detectar enlaces pendientes vigentes antes de crear uno nuevo.
 */
class PendingConsentController extends Controller
{
    use ApiResponse;

    /**
     * Lista los enlaces de firma enviados por la empresa, con filtros y paginación.
     *
     * Filtros disponibles via query params:
     * - status: filtra por estado (pending, confirmed, expired).
     *
     * Orden: created_at DESC (más recientes primero).
     * Paginación: 15 registros por página.
     * Eager loading: companyPolicy para evitar N+1 y obtener el nombre del documento.
     *
     * El email del destinatario se descifra en memoria via el accesor decrypted_pii.
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
     * Crea PendingConsents masivos y despacha los correos transaccionales.
     *
     * Flujo:
     * 1. Resuelve la empresa del usuario autenticado.
     * 2. Verifica que la política pertenezca a esa empresa y esté publicada.
     * 3. Itera el array de emails con prevención de duplicados por cada uno.
     * 4. Retorna respuesta consolidada con enviados y omitidos.
     *
     * Prevención de duplicados: usa pii_hash (SHA-256 del email, indexado) para
     * detectar enlaces pendientes vigentes antes de crear uno nuevo. Race
     * condition atrapada por índice único pending_uniqueness_key (1062 → omitido).
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
        $enviados = 0;
        $omitidos = 0;

        DB::beginTransaction();

        try {
            foreach ($emails as $email) {
                $piiHash = hash('sha256', $email);

                $existing = PendingConsent::where('pii_hash', $piiHash)
                    ->where('company_policy_id', $policy->id)
                    ->where('status', 'pending')
                    ->where('expires_at', '>', now())
                    ->first();

                if ($existing) {
                    $omitidos++;

                    continue;
                }

                try {
                    $pendingConsent = PendingConsent::create([
                        'company_id' => $company->id,
                        'company_policy_id' => $policy->id,
                        'token' => PendingConsent::generateToken(),
                        'pii_payload' => ['email' => $email],
                        'pii_hash' => $piiHash,
                        'status' => 'pending',
                        'source' => 'manual_panel',
                        'expires_at' => now()->addDays(7),
                    ]);
                } catch (QueryException $e) {
                    if ($e->errorInfo[1] ?? null === 1062) {
                        $omitidos++;

                        continue;
                    }

                    throw $e;
                }

                SendConsentLinkJob::dispatch($pendingConsent);
                $enviados++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            Log::warning('PendingConsent: error en envío masivo', [
                'error' => $e->getMessage(),
            ]);

            return $this->error('Error al procesar el envío masivo.', null, 500);
        }

        $statusCode = $enviados > 0 ? 201 : 200;

        return response()->json([
            'status' => true,
            'message' => "Proceso completado. {$enviados} enlaces enviados, {$omitidos} omitidos (ya estaban pendientes).",
            'data' => [
                'sent_count' => $enviados,
                'skipped_count' => $omitidos,
            ],
        ], $statusCode);
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
