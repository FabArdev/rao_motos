<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo base del dominio RAO MOTOS.
 *
 * Nombra en español las columnas de marca de tiempo que Eloquent gestiona solo:
 * las tablas propias usan creado_en / actualizado_en en vez de created_at / updated_at.
 * Todo modelo del dominio debe extender esta clase en lugar de Model.
 *
 * Excepción: Usuario extiende Authenticatable (lo exige Fortify) y por eso repite
 * estas constantes en su propia definición.
 */
abstract class ModeloBase extends Model
{
    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';
}
