<?php

namespace App\Models;

class PagoCuota extends ModeloBase
{
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

    /** Total a pagar = cuota + mora acumulada. */
    public function totalAPagar(): float
    {
        return (float) $this->monto_cuota + (float) $this->mora;
    }
}
