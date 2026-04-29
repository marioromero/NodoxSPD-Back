<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class CompleteOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->type === 'company';
    }

    public function rules(): array
    {
        return [
            'tax_id' => ['required', 'string', 'max:50'],
            'legal_address' => ['required', 'string', 'max:255'],

            // Obligatorio para todas las empresas
            'arco_contact_email' => ['required', 'email', 'max:255'],

            // Validación para el multiselect de sectores
            'sector_ids' => ['required', 'array', 'min:1'],
            'sector_ids.*' => ['integer', 'exists:sectors,id'],

            'legal_representative_name' => ['required', 'string', 'max:255'],
            'legal_representative_tax_id' => ['required', 'string', 'max:50'],

            'is_foreign_entity' => ['required', 'boolean'],

            'local_contact_for_foreign_entity' => ['required_if:is_foreign_entity,true', 'array', 'nullable'],
            'local_contact_for_foreign_entity.name' => ['required_with:local_contact_for_foreign_entity', 'string'],
            'local_contact_for_foreign_entity.rut' => ['required_with:local_contact_for_foreign_entity', 'string'],
            'local_contact_for_foreign_entity.email' => ['required_with:local_contact_for_foreign_entity', 'email'],
            'local_contact_for_foreign_entity.address' => ['required_with:local_contact_for_foreign_entity', 'string'],

            'dpo_contact' => ['nullable', 'array'],
            'dpo_contact.name' => ['required_with:dpo_contact', 'string'],
            'dpo_contact.email' => ['required_with:dpo_contact', 'email'],
            'dpo_contact.phone' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'tax_id.required' => 'El número de identificación fiscal de la empresa es obligatorio.',
            'arco_contact_email.required' => 'Debe proveer un correo oficial para la recepción de solicitudes ARCO+P.',
            'arco_contact_email.email' => 'El correo ARCO debe tener un formato válido.',
            'legal_representative_name.required' => 'Debe individualizar al representante legal (Ley 21.719).',
            'legal_representative_tax_id.required' => 'El RUT o ID del representante legal es obligatorio.',
            'local_contact_for_foreign_entity.required_if' => 'Las empresas extranjeras deben declarar un contacto legal con domicilio en Chile.',
            'sector_ids.required' => 'Debe seleccionar al menos un sector o rubro comercial.',
            'sector_ids.exists' => 'Uno o más sectores seleccionados no son válidos.',
        ];
    }
}
