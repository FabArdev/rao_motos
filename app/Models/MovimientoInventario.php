<?php

namespace App\Models;

class MovimientoInventario extends ModeloBase
{
    protected $table = 'movimiento_inventario';

    protected $fillable = ['inventario_id', 'tipo_movimiento', 'cantidad', 'motivo', 'fecha'];

    protected $casts = [
        'cantidad' => 'integer',
        'fecha' => 'datetime',
    ];

    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'inventario_id');
    }
}
