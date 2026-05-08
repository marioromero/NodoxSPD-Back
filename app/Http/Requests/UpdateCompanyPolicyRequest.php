<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization will be handled by the Policy
        return true;
    }

    public function rules(): array
    {
        return [
            // Permitir actualizaciones parciales para el flujo de wizard
            'wizard_data' => ['sometimes', 'array'],
        ];
    }
}
