<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  Cliente — Datos extra de un usuario que compra
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Un cliente es un usuario "con algo más": guarda su NIT/CI para
 *  facturar. No es una persona distinta del usuario, es la misma
 *  persona vista como comprador (por eso comparten el mismo id).
 *
 *  IMPLEMENTACIÓN
 *  - Tabla: cliente. Extiende ModeloBase.
 *  - Subtabla 1:1 de usuario: la PK es el id del usuario, NO es
 *    autoincremental ($incrementing = false).
 *  - Relaciones: usuario() (belongsTo por id), pedidos(), ventas().
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Models;

class Cliente extends ModeloBase
{
    protected $table = 'cliente';

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
