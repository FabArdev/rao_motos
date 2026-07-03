<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // protegido por middleware role:admin
    }

    public function rules(): array
    {
        $id = $this->route('usuario')->id ?? $this->route('usuario');

        return [
            'nombre' => ['required', 'string', 'max:100', "regex:/^[\pL\pM\s.'-]+$/u"],
            'apellidos' => ['required', 'string', 'max:100', "regex:/^[\pL\pM\s.'-]+$/u"],
            'ci' => ['required', 'string', 'max:20', 'regex:/^[0-9]{4,15}([-\s]?[0-9A-Za-z]{1,3})?$/', Rule::unique('users', 'ci')->ignore($id)],
            'telefono' => ['required', 'string', 'regex:/^[0-9+][0-9\s-]{6,14}$/'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'estado' => ['boolean'],
            'nit_ci' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.regex' => 'El nombre solo puede contener letras.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.regex' => 'Los apellidos solo pueden contener letras.',
            'ci.required' => 'El CI es obligatorio.',
            'ci.regex' => 'El CI debe ser numérico (con complemento opcional, p.ej. 1234567-1K).',
            'ci.unique' => 'Ya existe un usuario con ese CI.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.regex' => 'El teléfono debe contener solo números (7 a 15 dígitos).',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo no tiene un formato válido.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'role_id.required' => 'Debe seleccionar un rol.',
            'role_id.exists' => 'El rol seleccionado no es válido.',
            'nit_ci.regex' => 'El NIT/CI de facturación debe ser numérico.',
        ];
    }
}
