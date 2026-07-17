<?php

namespace App\Models;

class Venta extends ModeloBase
{
    protected $table = 'venta';

    protected $fillable = [
        'numero_venta', 'cliente_id', 'vendedor_id', 'fecha', 'monto_total',
        'tipo_venta', 'metodo_pago', 'estado',
        'pago_facil_id_transaccion', 'pago_facil_numero_pago',
        'pago_facil_imagen_qr', 'pago_facil_estado', 'pago_facil_respuesta_cruda',
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
