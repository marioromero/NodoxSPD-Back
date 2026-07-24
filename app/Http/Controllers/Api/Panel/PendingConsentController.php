<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\SendPortalLinkRequest;
use App\Jobs\SendConsentLinkJob;
use App\Models\CompanyPolicy;
use App\Models\PendingConsent;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     * Crea un PendingConsent y despacha el envío del correo transaccional.
     *
     * Flujo:
     * 1. Resuelve la empresa del usuario autenticado.
     * 2. Verifica que la política pertenezca a esa empresa y esté publicada.
     * 3. Genera el token criptográfico.
     * 4. Crea el PendingConsent (mutador cifra pii_payload automáticamente).
     * 5. Despacha SendConsentLinkJob en background.
     * 6. Retorna 201 con confirmación.
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

        $email = $request->validated('email');

        $pendingConsent = PendingConsent::create([
            'company_id' => $company->id,
            'company_policy_id' => $policy->id,
            'token' => PendingConsent::generateToken(),
            'pii_payload' => ['email' => $email],
            'pii_hash' => hash('sha256', $email),
            'status' => 'pending',
            'source' => 'manual_panel',
            'expires_at' => now()->addDays(7),
        ]);

        SendConsentLinkJob::dispatch($pendingConsent);

        return $this->success(
            'Enlace de firma enviado correctamente.',
            [
                'id' => $pendingConsent->id,
                'status' => $pendingConsent->status,
                'expires_at' => $pendingConsent->expires_at->toIso8601String(),
            ],
            201,
        );
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
