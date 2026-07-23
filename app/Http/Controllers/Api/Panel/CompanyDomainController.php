<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\UpdateCompanyDomainsRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Controlador del panel para la gestión de dominios autorizados de la empresa.
 *
 * Permite a la Pyme configurar la lista blanca de dominios que el middleware
 * cors.dynamic validará al recibir peticiones del Trust Widget embebido.
 * Sin esta configuración, el widget no puede comunicarse con el backend
 * desde el sitio de la Pyme (CORS denegado por defecto).
 */
class CompanyDomainController extends Controller
{
    use ApiResponse;

    /**
     * Actualiza la lista de dominios autorizados de la empresa autenticada.
     *
     * El array de dominios se persiste en company.allowed_domains (JSON).
     * El cache del middleware cors.dynamic se invalida automáticamente al
     * cambiar el valor, ya que usa Cache::remember con TTL de 12h.
     */
    public function update(UpdateCompanyDomainsRequest $request): JsonResponse
    {
        $company = $request->user()->company;

        $company->update([
            'allowed_domains' => $request->validated('domains'),
        ]);

        return $this->success(
            'Dominios actualizados correctamente.',
            ['allowed_domains' => $company->fresh()->allowed_domains],
        );
    }
}
