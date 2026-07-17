<?php

namespace App\Models;

class MetodoPago extends ModeloBase
{
    protected $table = 'metodo_pago';

    protected $fillable = ['nombre', 'activo'];

    protected $casts = ['activo' => 'boolean'];
}
