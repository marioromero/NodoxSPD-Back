<?php

namespace App\Http\Requests\Panel;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para la actualización de dominios autorizados de una empresa.
 *
 * Permite enviar un array de dominios (FQDN) que el middleware cors.dynamic
 * usará para validar los orígenes de las peticiones al Trust Widget público.
 *
 * Reglas:
 * - domains debe estar presente (present) para permitir vaciar la lista con [].
 * - Máximo 10 dominios por empresa.
 * - Cada dominio debe ser un FQDN válido: sin http://, sin rutas, sin IP.
 */
class UpdateCompanyDomainsRequest extends FormRequest
{
    /**
     * Endpoint del panel: requiere autenticación Sanctum.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'domains' => ['present', 'array', 'max:10'],
            'domains.*' => ['string', 'regex:/^(?!:\/\/)(?=.{1,255}$)((.{1,63}\.){1,127}(?![0-9]*$)[a-z0-9-]+\.?)$/i'],
        ];
    }
}
