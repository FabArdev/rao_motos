<?php

namespace App\Models;

class Inventario extends ModeloBase
{
    protected $table = 'inventario';

    protected $fillable = [
        'producto_id', 'stock_actual', 'stock_minimo',
        'tecnica_inventario', 'tecnica_costo', 'fecha_actualizacion',
    ];

    protected $casts = [
        'stock_actual' => 'integer',
        'stock_minimo' => 'integer',
        'fecha_actualizacion' => 'datetime',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class, 'inventario_id');
    }

    public function bajoMinimo(): bool
    {
        return $this->stock_actual < $this->stock_minimo;
    }
}
