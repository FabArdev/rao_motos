<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  Pedido — Solicitud de compra hecha por un cliente (web)
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Es el "carrito enviado": el cliente elige productos del catálogo
 *  y crea un pedido. El vendedor lo aprueba (y se vuelve venta) o
 *  lo rechaza con un motivo.
 *
 *  IMPLEMENTACIÓN
 *  - Tabla: pedido. Extiende ModeloBase.
 *  - Campos: cliente_id, fecha, estado, motivo_rechazo, venta_id.
 *  - Relaciones: cliente(), detalles() (líneas), venta() (si se
 *    aprobó y generó una venta).
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Models;

class Pedido extends ModeloBase
{
    protected $table = 'pedido';

    protected $fillable = ['cliente_id', 'fecha', 'estado', 'motivo_rechazo', 'venta_id'];

    protected $casts = ['fecha' => 'datetime'];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetallePedido::class, 'pedido_id');
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }
}
