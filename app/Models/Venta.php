<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  Venta — Una venta realizada
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Guarda cada venta: quién compró, quién vendió, cuánto, si fue
 *  al contado o a crédito, cómo se pagó y en qué estado está.
 *  También guarda los datos del cobro por QR (PagoFácil).
 *
 *  IMPLEMENTACIÓN
 *  - Tabla: venta. Extiende ModeloBase.
 *  - Estados: PENDIENTE -> PAGADA -> COMPLETADA (o ANULADA).
 *  - Columnas pago_facil_* guardan el QR y su estado.
 *  - Relaciones: cliente(), vendedor() (Usuario), detalles()
 *    (líneas), credito() (si es a cuotas).
 *  - esCredito(): true si tipo_venta = CREDITO.
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Models;

use App\Models\Concerns\TieneQrPagoFacil;

class Venta extends ModeloBase
{
    use TieneQrPagoFacil;

    protected $table = 'venta';

    protected $fillable = [
        'numero_venta', 'cliente_id', 'vendedor_id', 'fecha', 'monto_total',
        'tipo_venta', 'metodo_pago', 'estado',
        'pago_facil_id_transaccion', 'pago_facil_numero_pago',
        'pago_facil_imagen_qr', 'pago_facil_expira_en', 'pago_facil_estado', 'pago_facil_respuesta_cruda',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'monto_total' => 'decimal:2',
        'pago_facil_expira_en' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function vendedor()
    {
        return $this->belongsTo(Usuario::class, 'vendedor_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }

    public function credito()
    {
        return $this->hasOne(Credito::class, 'venta_id');
    }

    public function esCredito(): bool
    {
        return $this->tipo_venta === 'CREDITO';
    }
}
