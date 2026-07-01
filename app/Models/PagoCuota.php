<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoCuota extends Model
{
    protected $table = 'pago_cuota';

    protected $fillable = [
        'credito_id', 'numero_cuota', 'monto_cuota', 'fecha_vencimiento', 'fecha_pago',
        'mora', 'estado', 'metodo_pago_id',
        'pago_facil_transaction_id', 'pago_facil_payment_number',
        'pago_facil_qr_image', 'pago_facil_expires_at', 'pago_facil_status', 'pago_facil_raw_response',
    ];

    protected $casts = [
        'numero_cuota' => 'integer',
        'monto_cuota' => 'decimal:2',
        'mora' => 'decimal:2',
        'fecha_vencimiento' => 'date',
        'fecha_pago' => 'date',
        'pago_facil_expires_at' => 'datetime',
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
