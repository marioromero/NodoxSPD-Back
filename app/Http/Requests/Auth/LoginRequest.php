<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a hacer esta petición.
     */
    public function authorize(): bool
    {
        return true; // Siempre true para el login
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'type' => ['required', 'in:company,person'], // Selector de tipo
        ];
    }

    /**
     * Mensajes personalizados (Opcional, pero recomendado para Angular)
     */
    public function messages(): array
    {
        return [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El formato del correo no es válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'type.required' => 'Debe seleccionar el tipo de cuenta.',
            'type.in' => 'El tipo de cuenta seleccionado no es válido.',
        ];
    }
}
