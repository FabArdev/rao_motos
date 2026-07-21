<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  PagoCuota — Una cuota del calendario de un crédito
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Cada cuota que el cliente debe pagar: su número, monto, fecha
 *  de vencimiento, si ya se pagó y cuánta mora acumuló por atraso.
 *  Puede cobrarse por QR (PagoFácil).
 *
 *  IMPLEMENTACIÓN
 *  - Tabla: pago_cuota. Extiende ModeloBase.
 *  - Estados: PENDIENTE / PAGADO. Columnas pago_facil_* para el QR.
 *  - Relaciones: credito(), metodoPago().
 *  - totalAPagar(): monto_cuota + mora.
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Models;

use App\Models\Concerns\TieneQrPagoFacil;

class PagoCuota extends ModeloBase
{
    use TieneQrPagoFacil;

    protected $table = 'pago_cuota';

    protected $fillable = [
        'credito_id', 'numero_cuota', 'monto_cuota', 'fecha_vencimiento', 'fecha_pago',
        'mora', 'estado', 'metodo_pago_id',
        'pago_facil_id_transaccion', 'pago_facil_numero_pago',
        'pago_facil_imagen_qr', 'pago_facil_expira_en', 'pago_facil_estado', 'pago_facil_respuesta_cruda',
    ];

    protected $casts = [
        'numero_cuota' => 'integer',
        'monto_cuota' => 'decimal:2',
        'mora' => 'decimal:2',
        'fecha_vencimiento' => 'date',
        'fecha_pago' => 'date',
        'pago_facil_expira_en' => 'datetime',
    ];

    public function credito()
    {
        return $this->belongsTo(Credito::class, 'credito_id');
    }

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }

    public function totalAPagar(): float
    {
        return (float) $this->monto_cuota + (float) $this->mora;
    }
}
