<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  Rol — Perfil de permisos de un usuario
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Define el tipo de usuario y qué puede hacer: admin, vendedor,
 *  almacenero o cliente. Cada usuario tiene exactamente un rol.
 *
 *  IMPLEMENTACIÓN
 *  - Tabla: rol. Extiende ModeloBase.
 *  - Campos: nombre, descripcion.
 *  - Relaciones: usuarios(), itemsMenu() (menú del rol).
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Models;

class Rol extends ModeloBase
{
    protected $table = 'rol';

    protected $fillable = ['nombre', 'descripcion'];

    public function usuarios()
    {
        return $this->hasMany(Usuario::class);
    }

    public function itemsMenu()
    {
        return $this->hasMany(ItemMenu::class);
    }
}
