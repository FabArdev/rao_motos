<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'producto';

    protected $fillable = [
        'codigo', 'nombre', 'marca', 'modelo', 'descripcion',
        'precio_venta_base', 'precio_mayorista', 'cantidad_minima_mayorista',
        'foto_url', 'activo',
    ];

    protected $casts = [
        'precio_venta_base' => 'decimal:2',
        'precio_mayorista' => 'decimal:2',
        'cantidad_minima_mayorista' => 'integer',
        'activo' => 'boolean',
    ];

    public function inventario()
    {
        return $this->hasOne(Inventario::class, 'producto_id');
    }

    /**
     * Precio aplicable a una cantidad: mayorista si alcanza el umbral del producto,
     * minorista si no. No depende de tipo de cliente (no existe).
     */
    public function precioPara(int $cantidad): float
    {
        return $cantidad >= $this->cantidad_minima_mayorista
            ? (float) $this->precio_mayorista
            : (float) $this->precio_venta_base;
    }
}
