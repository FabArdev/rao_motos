<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  Credito — Venta a cuotas (financiamiento)
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Cuando una venta se paga en cuotas, este registro guarda el
 *  crédito: cuántas cuotas, el interés, cuánto falta por pagar y
 *  su estado (vigente, pagado o en mora).
 *
 *  IMPLEMENTACIÓN
 *  - Tabla: credito. Extiende ModeloBase. 1:1 con una venta.
 *  - Campos: numero_cuotas, tasa_interes, saldo_pendiente, estado.
 *  - Relaciones: venta(), cuotas() (calendario de PagoCuota).
 *  - Lo arma y mantiene CreditoService (RN8, RN16).
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Models;

class Credito extends ModeloBase
{
    protected $table = 'credito';

    protected $fillable = ['venta_id', 'numero_cuotas', 'tasa_interes', 'saldo_pendiente', 'estado'];

    protected $casts = [
        'numero_cuotas' => 'integer',
        'tasa_interes' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function cuotas()
    {
        return $this->hasMany(PagoCuota::class, 'credito_id');
    }
}
