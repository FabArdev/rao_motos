<?php

namespace App\Models;

class Configuracion extends ModeloBase
{
    protected $table = 'configuracion';

    protected $fillable = ['clave', 'valor', 'descripcion'];

    /** Lee un parámetro con fallback al default si no existe. */
    public static function valor(string $clave, $default = null)
    {
        $row = static::where('clave', $clave)->first();

        return $row ? $row->valor : $default;
    }
}
