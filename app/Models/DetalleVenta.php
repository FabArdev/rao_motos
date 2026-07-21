<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  DetalleVenta — Una línea de una venta
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Cada renglón de una venta: qué producto (o servicio), cuántos y
 *  a qué precio. Si no tiene producto, es mano de obra/servicio.
 *
 *  IMPLEMENTACIÓN
 *  - Tabla: detalle_venta. Extiende ModeloBase.
 *  - Campos: venta_id, producto_id, descripcion, cantidad,
 *    precio_unitario.
 *  - Relaciones: venta(), producto().
 *  - esServicio(): true cuando no hay producto_id asociado.
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Models;

class DetalleVenta extends ModeloBase
{
    protected $table = 'detalle_venta';

    protected $fillable = ['venta_id', 'producto_id', 'descripcion', 'cantidad', 'precio_unitario'];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function esServicio(): bool
    {
        return is_null($this->producto_id);
    }
}
