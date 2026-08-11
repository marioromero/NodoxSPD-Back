<?php

namespace App\Http\Requests\Panel;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del endpoint de preview de fines legales durante el wizard.
 *
 * Permite al frontend del panel obtener en tiempo real qué purposes están
 * activos según las respuestas parciales del wizard, sin necesidad de
 * guardar la política.
 */
class PreviewPurposesRequest extends FormRequest
{
    /** Requiere autenticación Sanctum (panel administrativo). */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', 'in:privacy_policy,cookie_policy,workers_policy'],
            'wizard_data' => ['present', 'array'],
        ];
    }
}
