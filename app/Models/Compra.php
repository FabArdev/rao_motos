<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  Compra — Compra de mercadería a un proveedor
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Registra cuando la tienda le compra productos a un proveedor
 *  para reponer stock: a quién, cuándo, por cuánto y en qué estado
 *  (pendiente o ya recibida).
 *
 *  IMPLEMENTACIÓN
 *  - Tabla: compra. Extiende ModeloBase.
 *  - Campos: proveedor_id, fecha, total, estado.
 *  - Relaciones: proveedor(), detalles() (líneas de productos).
 *  - Al marcarse RECIBIDA se suma stock y se recalculan precios (RN23).
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Models;

class Compra extends ModeloBase
{
    protected $table = 'compra';

    protected $fillable = ['proveedor_id', 'fecha', 'total', 'estado'];

    protected $casts = [
        'fecha' => 'datetime',
        'total' => 'decimal:2',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleCompra::class, 'compra_id');
    }
}
