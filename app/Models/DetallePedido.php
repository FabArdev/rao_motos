<?php

namespace App\Models;

class DetallePedido extends ModeloBase
{
    protected $table = 'detalle_pedido';

    protected $fillable = ['pedido_id', 'producto_id', 'cantidad'];

    protected $casts = ['cantidad' => 'integer'];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
