<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  Bitacora — Registro de auditoría (quién hizo qué)
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Es el "libro de actas" del sistema: anota los eventos de
 *  seguridad, como inicios de sesión (correctos o fallidos) y
 *  accesos a recursos, con la IP y el navegador usados.
 *
 *  IMPLEMENTACIÓN
 *  - Tabla: bitacora. Extiende Model (sin timestamps, usa 'fecha').
 *  - Campos: usuario_id, correo, accion, recurso, ip,
 *    agente_usuario, fecha.
 *  - Relación: usuario(). Requisito transversal REQ4.
 *  - Acciones típicas: LOGIN_OK, LOGIN_FAIL, ACCESO_RECURSO.
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bitacora extends Model
{
    protected $table = 'bitacora';

    public $timestamps = false;

    protected $fillable = ['usuario_id', 'correo', 'accion', 'recurso', 'ip', 'agente_usuario', 'fecha'];

    protected $casts = ['fecha' => 'datetime'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
