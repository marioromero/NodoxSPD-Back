<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'], // Requiere campo password_confirmation
            'type' => ['required', 'in:company,person'],
        ];

        // Si elige empresa, exigimos el nombre del negocio
        if ($this->type === 'company') {
            $rules['business_name'] = ['required', 'string', 'max:255'];
        }

        // Si elige persona, exigimos nombre y apellido
        if ($this->type === 'person') {
            $rules['first_name'] = ['required', 'string', 'max:255'];
            $rules['last_name'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }
}
