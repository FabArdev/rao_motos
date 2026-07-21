<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  DetallePedido — Una línea de un pedido web
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Cada producto que un cliente puso en su pedido, con la cantidad
 *  que quiere. Un pedido tiene muchas de estas líneas.
 *
 *  IMPLEMENTACIÓN
 *  - Tabla: detalle_pedido. Extiende ModeloBase.
 *  - Campos: pedido_id, producto_id, cantidad.
 *  - Relaciones: pedido(), producto().
 * ─────────────────────────────────────────────────────────────
 */

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
