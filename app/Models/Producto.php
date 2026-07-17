<?php

namespace App\Models;

class Producto extends ModeloBase
{
    protected $table = 'producto';

    protected $fillable = [
        'codigo', 'nombre', 'marca', 'modelo', 'descripcion',
        'precio_venta_base', 'precio_mayorista', 'cantidad_minima_mayorista',
        'foto_url', 'activo',
    ];

    protected $appends = ['foto_completa'];

    protected $casts = [
        'precio_venta_base' => 'decimal:2',
        'precio_mayorista' => 'decimal:2',
        'cantidad_minima_mayorista' => 'integer',
        'activo' => 'boolean',
    ];

    public function getFotoCompletaAttribute(): ?string
    {
        return $this->foto_url ? asset('storage/'.$this->foto_url) : null;
    }

    public function inventario()
    {
        return $this->hasOne(Inventario::class, 'producto_id');
    }

    /** Galería de imágenes adicionales (además de la portada foto_url). */
    public function imagenes()
    {
        return $this->hasMany(ProductoImagen::class, 'producto_id')->orderBy('orden');
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
