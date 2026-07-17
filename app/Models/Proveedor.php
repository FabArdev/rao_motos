<?php

namespace App\Models;

class Proveedor extends ModeloBase
{
    protected $table = 'proveedor';

    protected $fillable = ['razon_social', 'contacto_principal', 'nit', 'telefono', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function compras()
    {
        return $this->hasMany(Compra::class, 'proveedor_id');
    }
}
