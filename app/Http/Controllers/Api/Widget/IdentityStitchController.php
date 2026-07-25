<?php

namespace App\Http\Controllers\Api\Widget;

use App\Http\Controllers\Controller;
use App\Http\Requests\Widget\IdentityStitchRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controlador público para el motor de Identity Stitching.
 *
 * Permite a una empresa vincular el historial de consentimientos anónimos
 * del Trust Widget (identificados por visitor_uuid) con un usuario real
 * de su sistema (identificado por user_ref).
 *
 * Seguridad:
 * - La autenticación se hace via HMAC-SHA256 firmado con el integration_secret
 *   de la empresa. No hay sesión ni Bearer token.
 * - Ventana temporal de bloques de 5 minutos (300 segundos) con tolerancia
 *   al bloque inmediatamente anterior (relojes desincronizados).
 * - hash_equals() para prevenir timing attacks en la comparación del HMAC.
 *
 * Inmutabilidad:
 * - Este endpoint SOLO escribe en la tabla pivote person_visitor_uuids.
 * - El ledger inmutable (consent_logs) nunca se modifica ni se lee aquí.
 */
class IdentityStitchController extends Controller
{
    /**
     * Vincula un visitor_uuid anónimo con una referencia de usuario externa.
     *
     * Flujo estricto:
     * A. Resuelve la empresa y verifica que tenga integration_secret configurado.
     * B. Valida el HMAC contra bloques de tiempo de 5 minutos.
     * C. Genera el external_ref_hash determinista (SHA-256 de company_id:user_ref).
     * D. Upsert seguro en person_visitor_uuids via unique key (external_ref_hash, visitor_uuid).
     * E. Retorna 200 con {"status": "synced"}.
     */
    public function sync(IdentityStitchRequest $request): JsonResponse
    {
        $company = Company::where('public_uuid', $request->input('company_public_uuid'))->first();

        if (! $company || ! $company->integration_secret) {
            return response()->json(['error' => 'widget_not_configured'], 404);
        }

        $currentBlock = (int) floor(time() / 300);
        $receivedBlock = (int) floor($request->input('timestamp') / 300);

        if (! in_array($receivedBlock, [$currentBlock, $currentBlock - 1], true)) {
            return response()->json(['error' => 'hmac_expired'], 401);
        }

        $expectedHmac = hash_hmac(
            'sha256',
            $request->input('user_ref').':'.$receivedBlock,
            $company->integration_secret,
        );

        if (! hash_equals($expectedHmac, $request->input('hmac'))) {
            Log::warning('IdentityStitch: HMAC inválido', [
                'company_id' => $company->id,
                'received_block' => $receivedBlock,
            ]);

            return response()->json(['error' => 'invalid_hmac'], 401);
        }

        $externalRefHash = hash('sha256', $company->id.':'.$request->input('user_ref'));

        DB::table('person_visitor_uuids')->upsert(
            [[
                'company_id' => $company->id,
                'person_id' => null,
                'external_ref_hash' => $externalRefHash,
                'visitor_uuid' => $request->input('visitor_uuid'),
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['external_ref_hash', 'visitor_uuid'],
            ['updated_at'],
        );

        return response()->json(['status' => 'synced'], 200);
    }
}
