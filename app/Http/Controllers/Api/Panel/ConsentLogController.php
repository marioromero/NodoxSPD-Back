<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Models\ConsentLog;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador del panel de administración para la auditoría de consentimientos.
 *
 * Expone el ledger inmutable (consent_logs) con soporte para filtrado por
 * método de captura, hash de política y rango de fechas. Los resultados
 * siempre se acotan a la empresa del usuario autenticado y se paginan.
 *
 * Seguridad clave:
 * - El scope es siempre por empresa (company_id del usuario autenticado).
 * - No se expone el ID interno de la empresa ni datos sensibles del visitante
 *   más allá del visitor_uuid (que ya es un identificador seudonimizado).
 * - El campo identifier se retorna forzado a null hasta que se implemente
 *   Identity Stitching (tabla person_visitor_uuids).
 */
class ConsentLogController extends Controller
{
    use ApiResponse;

    /**
     * Retorna los registros de consentimiento paginados y filtrados.
     *
     * Filtros disponibles via query params:
     * - capture_method: filtra por método de captura (live_widget, live_portal, bulk_import).
     * - policy_hash: filtra por el hash inmutable de la política.
     * - from / to: rango de fechas sobre consent_occurred_at (formato Y-m-d).
     *
     * Orden: consent_occurred_at DESC (más recientes primero).
     * Paginación: 25 registros por página.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company->id;

        $query = ConsentLog::where('company_id', $companyId)
            ->orderBy('consent_occurred_at', 'desc');

        if ($request->filled('capture_method')) {
            $query->where('capture_method', $request->input('capture_method'));
        }

        if ($request->filled('policy_hash')) {
            $query->where('policy_hash', $request->input('policy_hash'));
        }

        if ($request->filled('from')) {
            $query->where('consent_occurred_at', '>=', $request->input('from').' 00:00:00');
        }

        if ($request->filled('to')) {
            $query->where('consent_occurred_at', '<=', $request->input('to').' 23:59:59');
        }

        $logs = $query->paginate(25);

        $transformed = $logs->getCollection()->map(fn (ConsentLog $log): array => [
            'id' => $log->id,
            'visitor_uuid' => $log->visitor_uuid,
            'identifier' => null,
            'purposes' => $log->purposes,
            'policy_hash' => $log->policy_hash,
            'proof_hash' => $log->proof_hash,
            'capture_method' => $log->capture_method,
            'consent_occurred_at' => $log->consent_occurred_at->toIso8601String(),
            'recorded_at' => $log->created_at->toIso8601String(),
        ]);

        return $this->success('Registros de consentimiento.', [
            'data' => $transformed,
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
            ],
        ]);
    }
}
