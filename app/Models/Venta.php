<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $table = 'venta';

    protected $fillable = [
        'numero_venta', 'cliente_id', 'vendedor_id', 'fecha', 'monto_total',
        'tipo_venta', 'metodo_pago', 'estado',
        'pago_facil_transaction_id', 'pago_facil_payment_number',
        'pago_facil_qr_image', 'pago_facil_status', 'pago_facil_raw_response',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'monto_total' => 'decimal:2',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'vendedor_id');
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
