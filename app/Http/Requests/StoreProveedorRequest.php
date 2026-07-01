<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'razon_social' => ['required', 'string', 'max:255'],
            'contacto_principal' => ['nullable', 'string', 'max:255'],
            'nit' => ['nullable', 'string', 'max:20'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'activo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'razon_social.required' => 'La razón social es obligatoria.',
            'razon_social.max' => 'La razón social no puede superar los 255 caracteres.',
            'nit.max' => 'El NIT no puede superar los 20 caracteres.',
            'telefono.max' => 'El teléfono no puede superar los 20 caracteres.',
        ];
    }
}
