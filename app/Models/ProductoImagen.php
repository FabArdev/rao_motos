<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  ProductoImagen — Imagen adicional de un producto (galería)
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Las fotos extra de un producto, además de la portada, para el
 *  carrusel/galería. Cada una tiene un orden de aparición.
 *
 *  IMPLEMENTACIÓN
 *  - Tabla: producto_imagen. Extiende ModeloBase.
 *  - Campos: producto_id, ruta, orden.
 *  - Accessor url: arma la URL pública desde storage.
 *  - Relación: producto().
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Models;

class ProductoImagen extends ModeloBase
{
    protected $table = 'producto_imagen';

    protected $fillable = ['producto_id', 'ruta', 'orden'];

    protected $appends = ['url'];

    protected $casts = [
        'orden' => 'integer',
    ];

    public function getUrlAttribute(): ?string
    {
        return \App\Support\Media::url($this->ruta);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
