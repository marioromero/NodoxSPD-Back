<?php

namespace App\Http\Requests\Panel;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del envío masivo de enlaces de firma desde el panel.
 *
 * La Pyme envía un array de emails (máximo 50) y el ID de la política a firmar.
 * El controlador verifica que la política pertenezca a la empresa del usuario
 * autenticado antes de iterar y crear los registros PendingConsent.
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
     * emails es un array de máximo 50 correos para evitar saturar la cola.
     * company_policy_id es integer (bigIncrements en company_policies, no UUID).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'emails' => ['required', 'array', 'min:1', 'max:50'],
            'emails.*' => ['required', 'email', 'max:255'],
            'company_policy_id' => ['required', 'integer', 'exists:company_policies,id'],
        ];
    }
}
