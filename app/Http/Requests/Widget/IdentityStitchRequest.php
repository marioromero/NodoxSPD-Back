<?php

namespace App\Http\Requests\Widget;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del endpoint público de Identity Stitching.
 *
 * La empresa invoca este endpoint desde su frontend para vincular un
 * visitor_uuid anónimo (generado por el Trust Widget) con una referencia
 * de usuario interna (user_ref, ej: email, RUT, employee_id).
 *
 * La seguridad se garantiza via HMAC-SHA256 firmado con el integration_secret
 * de la empresa, con tolerancia de bloques de tiempo de 5 minutos.
 */
class IdentityStitchRequest extends FormRequest
{
    /** Endpoint público: la autenticación se hace via HMAC, no via sesión. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación del payload de stitching.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'visitor_uuid' => ['required', 'uuid'],
            'company_public_uuid' => ['required', 'uuid', 'exists:companies,public_uuid'],
            'user_ref' => ['required', 'string', 'max:191'],
            'timestamp' => ['required', 'integer'],
            'hmac' => ['required', 'string', 'size:64'],
        ];
    }
}
