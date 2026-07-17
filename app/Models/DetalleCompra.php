<?php

namespace App\Models;

class DetalleCompra extends ModeloBase
{
    protected $table = 'detalle_compra';

    protected $fillable = ['compra_id', 'producto_id', 'cantidad', 'precio_unitario'];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
    ];

    public function compra()
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
