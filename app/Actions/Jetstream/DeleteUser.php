<?php

namespace App\Actions\Jetstream;

use App\Models\Usuario;
use Laravel\Jetstream\Contracts\DeletesUsers;

class DeleteUser implements DeletesUsers
{
    /**
     * Delete the given user.
     */
    public function delete(Usuario $usuario): void
    {
        $usuario->deleteProfilePhoto();
        $usuario->tokens->each->delete();
        $usuario->delete();
    }
}
