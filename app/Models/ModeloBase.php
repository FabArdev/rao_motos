<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  ModeloBase — Modelo padre de todo el dominio RAO MOTOS
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Es el "molde" del que heredan casi todos los modelos del
 *  sistema. Su único trabajo es decirle a Laravel que las fechas
 *  automáticas de creación/edición se llaman en español
 *  (creado_en / actualizado_en) y no en inglés. Así ningún modelo
 *  se rompe al guardarse.
 *
 *  IMPLEMENTACIÓN
 *  - Clase abstracta que extiende Eloquent\Model.
 *  - Redefine las constantes CREATED_AT y UPDATED_AT.
 *  - Todo modelo del dominio debe extender ModeloBase (no Model).
 *  - Excepción: Usuario extiende Authenticatable (lo exige Fortify)
 *    y repite estas constantes por su cuenta.
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class ModeloBase extends Model
{
    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';
}
