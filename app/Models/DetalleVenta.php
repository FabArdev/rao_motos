<?php

namespace App\Models;

class DetalleVenta extends ModeloBase
{
    protected $table = 'detalle_venta';

    protected $fillable = ['venta_id', 'producto_id', 'descripcion', 'cantidad', 'precio_unitario'];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /** Una línea es servicio/mano de obra cuando no tiene producto asociado. */
    public function esServicio(): bool
    {
        return is_null($this->producto_id);
    }
}
