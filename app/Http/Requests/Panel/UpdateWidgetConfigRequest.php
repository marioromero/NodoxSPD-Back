<?php

namespace App\Http\Requests\Panel;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para la actualización de la configuración visual del Trust Widget.
 *
 * Permite a la Pyme personalizar color primario, logo, textos del banner y
 * etiquetas de botones. Los campos son todos opcionales (nullable) para
 * permitir actualizaciones parciales sin enviar el JSON completo.
 *
 * Los textos del banner van anidados bajo legal_texts para coincidir con
 * la estructura que WidgetConfigController lee al servir la config pública.
 */
class UpdateWidgetConfigRequest extends FormRequest
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
            'primary_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'logo_url' => ['nullable', 'url', 'max:2048'],
            'legal_texts' => ['nullable', 'array'],
            'legal_texts.banner_title' => ['nullable', 'string', 'max:255'],
            'legal_texts.banner_body' => ['nullable', 'string', 'max:1000'],
            'button_accept_text' => ['nullable', 'string', 'max:50'],
            'button_reject_text' => ['nullable', 'string', 'max:50'],
        ];
    }
}
