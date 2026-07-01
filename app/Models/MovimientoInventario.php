<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
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
