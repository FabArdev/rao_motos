<?php

namespace App\Listeners;

use App\Models\Bitacora;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;

/**
 * Registra en bitácora los intentos de inicio de sesión (REQ4 / L11).
 */
class RegistrarLoginBitacora
{
    public function handleLogin(Login $event): void
    {
        Bitacora::create([
            'usuario_id' => $event->user->id ?? null,
            'email' => $event->user->email ?? null,
            'accion' => 'LOGIN_OK',
            'recurso' => 'auth',
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'fecha' => now(),
        ]);
    }

    public function handleFailed(Failed $event): void
    {
        Bitacora::create([
            'usuario_id' => $event->user->id ?? null,
            'email' => $event->credentials['email'] ?? null,
            'accion' => 'LOGIN_FAIL',
            'recurso' => 'auth',
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'fecha' => now(),
        ]);
    }
}
