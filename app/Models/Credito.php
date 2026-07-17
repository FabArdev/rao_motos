<?php

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
