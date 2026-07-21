<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  MovimientoInventario — Historial de entradas/salidas de stock
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Cada vez que el stock sube o baja se anota aquí: cuánto, de qué
 *  tipo (ingreso/egreso) y por qué motivo. Es el historial que
 *  permite rastrear el porqué de cada cambio de stock.
 *
 *  IMPLEMENTACIÓN
 *  - Tabla: movimiento_inventario. Extiende ModeloBase.
 *  - Campos: inventario_id, tipo_movimiento (INGRESO/EGRESO),
 *    cantidad, motivo, fecha.
 *  - Relación: inventario(). Lo crea InventarioService.
 * ─────────────────────────────────────────────────────────────
 */

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
