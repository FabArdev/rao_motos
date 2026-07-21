<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  Inventario — Stock de un producto
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Lleva cuántas unidades hay de un producto y cuál es el mínimo
 *  antes de tener que reponer. Sabe avisar cuando el stock cayó
 *  por debajo de ese mínimo.
 *
 *  IMPLEMENTACIÓN
 *  - Tabla: inventario. Extiende ModeloBase. 1:1 con producto.
 *  - Campos: stock_actual, stock_minimo, tecnica_inventario,
 *    tecnica_costo, fecha_actualizacion.
 *  - Relaciones: producto(), movimientos() (historial).
 *  - bajoMinimo(): true si stock_actual < stock_minimo.
 *  - El stock se mueve sólo vía InventarioService.
 * ─────────────────────────────────────────────────────────────
 */

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
