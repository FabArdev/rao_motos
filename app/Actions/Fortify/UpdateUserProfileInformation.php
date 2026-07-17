<?php

namespace App\Actions\Fortify;

use App\Models\Usuario;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Valida y actualiza los datos de perfil del usuario indicado.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(Usuario $usuario, array $input): void
    {
        Validator::make($input, [
            'nombre' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'correo' => ['required', 'email', 'max:255', Rule::unique('usuario')->ignore($usuario->id)],
            'foto' => ['nullable', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'El correo no es válido.',
            'correo.unique' => 'Ese correo ya está en uso.',
            'foto.mimes' => 'La foto debe ser JPG, PNG o WEBP.',
            'foto.max' => 'La foto no debe superar 5 MB.',
        ])->validateWithBag('updateProfileInformation');

        if (isset($input['foto'])) {
            $usuario->updateProfilePhoto($input['foto']);
        }

        if ($input['correo'] !== $usuario->correo &&
            $usuario instanceof MustVerifyEmail) {
            $this->actualizarUsuarioVerificado($usuario, $input);
        } else {
            $usuario->forceFill([
                'nombre' => $input['nombre'],
                'apellidos' => $input['apellidos'],
                'correo' => $input['correo'],
            ])->save();
        }
    }

    /**
     * Actualiza los datos de perfil de un usuario con correo verificado.
     *
     * @param  array<string, string>  $input
     */
    protected function actualizarUsuarioVerificado(Usuario $usuario, array $input): void
    {
        $usuario->forceFill([
            'nombre' => $input['nombre'],
            'apellidos' => $input['apellidos'],
            'correo' => $input['correo'],
            'correo_verificado_en' => null,
        ])->save();

        $usuario->sendEmailVerificationNotification();
    }
}
