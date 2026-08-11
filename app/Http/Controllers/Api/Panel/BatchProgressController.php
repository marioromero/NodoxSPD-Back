<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Models\CompanyBatch;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;

/**
 * Controlador de monitoreo de lotes de envío (Bus::batch).
 *
 * Permite a la Pyme consultar el progreso de un lote de envíos masivos
 * de enlaces de firma. Aislamiento multitenant: verifica que el batch
 * pertenezca a la empresa del usuario autenticado via company_batches.
 *
 * Seguridad:
 * - Si el batch no existe o no pertenece a la empresa, retorna 404.
 * - El usuario debe estar autenticado (auth:sanctum).
 */
class BatchProgressController extends Controller
{
    use ApiResponse;

    /**
     * Retorna el progreso de un batch específico.
     *
     * Validación multitenant: busca en company_batches si el batch_id
     * pertenece a la empresa del usuario autenticado. Si no, 404 (no revela
     * la existencia del batch a otras empresas).
     *
     * @param  Request  $request  Request con usuario autenticado.
     * @param  string  $id  UUID del batch (Bus::batch id).
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $companyId = $request->user()->company->id;

        $companyBatch = CompanyBatch::where('company_id', $companyId)
            ->where('batch_id', $id)
            ->first();

        if (! $companyBatch) {
            return $this->error('Lote no encontrado.', null, 404);
        }

        $batch = Bus::findBatch($id);

        if (! $batch) {
            return $this->error('Lote no encontrado.', null, 404);
        }

        return $this->success('Progreso del lote.', [
            'id' => $batch->id,
            'name' => $batch->name,
            'total_jobs' => $batch->totalJobs,
            'pending_jobs' => $batch->pendingJobs,
            'processed_jobs' => $batch->processedJobs(),
            'failed_jobs' => $batch->failedJobs,
            'progress' => round($batch->progress(), 2),
            'cancelled' => $batch->cancelled(),
            'finished' => $batch->finished(),
            'created_at' => $batch->createdAt?->toIso8601String(),
            'finished_at' => $batch->finishedAt?->toIso8601String(),
        ]);
    }
}
