<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  Proveedor — Empresa que abastece a la tienda
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Los proveedores a los que la tienda les compra repuestos:
 *  su razón social, contacto, NIT y teléfono.
 *
 *  IMPLEMENTACIÓN
 *  - Tabla: proveedor. Extiende ModeloBase.
 *  - Campos: razon_social, contacto_principal, nit, telefono, activo.
 *  - Relación: compras().
 * ─────────────────────────────────────────────────────────────
 */

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
