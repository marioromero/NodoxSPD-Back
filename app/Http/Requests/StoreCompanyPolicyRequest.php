<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Solo usuarios con una empresa asociada pueden crear políticas
        return $this->user()->company()->exists();
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', 'exists:legal_templates,document_type'],
            // El wizard_data es opcional para el POST inicial (wizard flow)
            'wizard_data' => ['sometimes', 'array'],
        ];
    }
}
