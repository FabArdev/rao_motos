<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleOrden extends Model
{
    protected $table = 'detalle_orden';

    protected $fillable = ['orden_trabajo_id', 'producto_id', 'cantidad', 'estado', 'motivo'];

    protected $casts = ['cantidad' => 'integer'];

    public function ordenTrabajo()
    {
        return $this->belongsTo(OrdenTrabajo::class, 'orden_trabajo_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
