<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  Producto — Repuesto/artículo que se vende
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Es cada repuesto de moto del catálogo: su código, nombre, marca,
 *  fotos y precios. Sabe decir qué precio corresponde según cuánto
 *  se compre (normal o mayorista si se lleva bastante).
 *
 *  IMPLEMENTACIÓN
 *  - Tabla: producto. Extiende ModeloBase.
 *  - Campos clave: precio_venta_base, precio_mayorista,
 *    cantidad_minima_mayorista, foto_url, activo.
 *  - Accessor foto_completa: arma la URL pública desde storage.
 *  - Relaciones: inventario() (1:1), imagenes() (galería ordenada).
 *  - precioPara($cantidad): devuelve precio mayorista si la cantidad
 *    alcanza el umbral del producto, si no el minorista (RN3).
 * ─────────────────────────────────────────────────────────────
 */

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

    public function imagenes()
    {
        return $this->hasMany(ProductoImagen::class, 'producto_id')->orderBy('orden');
    }

    public function precioPara(int $cantidad): float
    {
        return $cantidad >= $this->cantidad_minima_mayorista
            ? (float) $this->precio_mayorista
            : (float) $this->precio_venta_base;
    }
}
