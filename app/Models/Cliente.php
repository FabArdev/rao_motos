<?php

namespace App\Models;

class Cliente extends ModeloBase
{
    protected $table = 'cliente';

    // El PK es el mismo id de usuario (herencia 1:1), no autoincremental.
    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = ['id', 'nit_ci'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id');
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'cliente_id');
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'cliente_id');
    }
}
