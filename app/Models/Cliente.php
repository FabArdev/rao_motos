<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'cliente';

    // El PK es el mismo id de users (herencia 1:1), no autoincremental.
    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = ['id', 'nit_ci'];

    public function user()
    {
        return $this->belongsTo(User::class, 'id');
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
