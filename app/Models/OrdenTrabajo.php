<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenTrabajo extends Model
{
    protected $table = 'orden_trabajo';

    protected $fillable = [
        'cliente_id', 'moto_id', 'fecha_ingreso', 'descripcion_problema',
        'diagnostico', 'fecha_diagnostico', 'costo_estimado_mano_obra',
        'costo_estimado_repuestos', 'presupuesto_aprobado', 'costo_mano_obra',
        'venta_id', 'estado',
    ];

    protected $casts = [
        'fecha_ingreso' => 'datetime',
        'fecha_diagnostico' => 'datetime',
        'costo_estimado_mano_obra' => 'decimal:2',
        'costo_estimado_repuestos' => 'decimal:2',
        'costo_mano_obra' => 'decimal:2',
        'presupuesto_aprobado' => 'boolean',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function moto()
    {
        return $this->belongsTo(Moto::class, 'moto_id');
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleOrden::class, 'orden_trabajo_id');
    }
}
