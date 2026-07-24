<?php

namespace App\Http\Requests\Portal;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la firma de consentimiento desde el Portal Cautivo.
 *
 * El destinatario del correo transaccional envía sus decisiones de fines
 * legales junto con su visitor_uuid (generado y mantenido en localStorage
 * por el frontend del portal, para trazabilidad de sesión/dispositivo).
 *
 * A diferencia del Trust Widget, no se valida company_public_uuid ni timestamp
 * porque el token criptográfico en la URL ya identifica la empresa + política,
 * y el timestamp se genera server-side (now()) por mayor confianza.
 */
class ConfirmPortalConsentRequest extends FormRequest
{
    /** Endpoint público: el portal cautivo no requiere autenticación. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación base.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'purposes' => ['required', 'array'],
            'purposes.*' => ['boolean'],
            'visitor_uuid' => ['required', 'uuid'],
        ];
    }
}
