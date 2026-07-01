<?php

namespace App\Actions\Fortify;

use App\Models\Cliente;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'nombre' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/'],
            'apellidos' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/'],
            'ci' => ['required', 'string', 'min:6', 'max:20', 'unique:users,ci', 'regex:/^[0-9]+$/'],
            'telefono' => ['required', 'string', 'min:7', 'max:15', 'regex:/^[0-9]+$/'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ], [
            // Mensajes para nombre
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos :min caracteres.',
            'nombre.max' => 'El nombre no puede exceder :max caracteres.',
            'nombre.regex' => 'El nombre solo puede contener letras y espacios.',

            // Mensajes para apellidos
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.min' => 'Los apellidos deben tener al menos :min caracteres.',
            'apellidos.max' => 'Los apellidos no pueden exceder :max caracteres.',
            'apellidos.regex' => 'Los apellidos solo pueden contener letras y espacios.',

            // Mensajes para CI
            'ci.required' => 'El CI es obligatorio.',
            'ci.min' => 'El CI debe tener al menos :min dígitos.',
            'ci.max' => 'El CI no puede exceder :max dígitos.',
            'ci.unique' => 'Este CI ya está registrado en el sistema.',
            'ci.regex' => 'El CI solo puede contener números.',

            // Mensajes para teléfono
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.min' => 'El teléfono debe tener al menos :min dígitos.',
            'telefono.max' => 'El teléfono no puede exceder :max dígitos.',
            'telefono.regex' => 'El teléfono solo puede contener números.',

            // Mensajes para email
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ingresar un correo electrónico válido.',
            'email.max' => 'El correo electrónico no puede exceder :max caracteres.',
            'email.unique' => 'Este correo electrónico ya está registrado.',

            // Mensajes para fecha de nacimiento
            'fecha_nacimiento.date' => 'La fecha de nacimiento debe ser una fecha válida.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'fecha_nacimiento.after' => 'La fecha de nacimiento debe ser posterior a 1900.',

            // Mensajes para password
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'password.regex' => 'La contraseña debe contener al menos una letra mayúscula, una minúscula y un número.',

            // Términos
            'terms.accepted' => 'Debe aceptar los términos y condiciones para continuar.',
        ])->validate();

        // Registro público = cliente. Usuario + fila cliente de forma atómica (RN14).
        $clienteRoleId = Role::where('nombre', 'cliente')->value('id');

        return DB::transaction(function () use ($input, $clienteRoleId) {
            $user = User::create([
                'nombre' => $input['nombre'],
                'apellidos' => $input['apellidos'],
                'ci' => $input['ci'],
                'telefono' => $input['telefono'],
                'email' => $input['email'],
                'fecha_nacimiento' => $input['fecha_nacimiento'] ?? null,
                'password' => Hash::make($input['password']),
                'role_id' => $clienteRoleId,
                'estado' => true,
            ]);

            Cliente::create(['id' => $user->id, 'nit_ci' => $input['ci']]);

            return $user;
        });
    }
}
