<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  Configuracion — Parámetros ajustables del sistema
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Es la "caja de perillas" del sistema: guarda valores que el
 *  administrador puede cambiar sin tocar código, como la tasa de
 *  interés, los días entre cuotas o los márgenes de precio.
 *
 *  IMPLEMENTACIÓN
 *  - Tabla: configuracion (clave, valor, descripcion).
 *  - Extiende ModeloBase.
 *  - valor($clave, $default): lee un parámetro y, si no existe,
 *    devuelve el valor por defecto (RN17).
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Models;

class Configuracion extends ModeloBase
{
    protected $table = 'configuracion';

    protected $fillable = ['clave', 'valor', 'descripcion'];

    public static function valor(string $clave, $default = null)
    {
        $row = static::where('clave', $clave)->first();

        return $row ? $row->valor : $default;
    }
}
