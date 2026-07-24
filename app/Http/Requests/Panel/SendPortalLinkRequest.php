<?php

namespace App\Http\Requests\Panel;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del envío de enlaces de firma desde el panel de administración.
 *
 * La Pyme envía el email del destinatario y el ID de la política a firmar.
 * El controlador verifica que la política pertenezca a la empresa del usuario
 * autenticado antes de crear el PendingConsent.
 */
class SendPortalLinkRequest extends FormRequest
{
    /** Requiere autenticación Sanctum + rol company_admin (validado en ruta). */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación.
     *
     * company_policy_id es integer (bigIncrements en company_policies, no UUID).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'company_policy_id' => ['required', 'integer', 'exists:company_policies,id'],
        ];
    }
}
