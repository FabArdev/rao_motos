<?php

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
        return $this->ruta ? asset('storage/'.$this->ruta) : null;
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
