<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  ItemMenu — Opción del menú de navegación (por rol)
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Cada botón/enlace del menú superior. El menú se arma solo según
 *  el rol del usuario: un vendedor ve unas opciones y un almacenero
 *  otras. Puede tener submenús (opciones "hijas").
 *
 *  IMPLEMENTACIÓN
 *  - Tabla: item_menu. Extiende ModeloBase. Requisito REQ2.
 *  - Campos: etiqueta, ruta_laravel, icono, orden, rol_id,
 *    padre_id, activo.
 *  - Relaciones: rol(), padre(), hijos() (submenús, ordenados).
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Models;

class ItemMenu extends ModeloBase
{
    protected $table = 'item_menu';

    protected $fillable = ['etiqueta', 'ruta_laravel', 'icono', 'orden', 'rol_id', 'padre_id', 'activo'];

    protected $casts = [
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function padre()
    {
        return $this->belongsTo(ItemMenu::class, 'padre_id');
    }

    public function hijos()
    {
        return $this->hasMany(ItemMenu::class, 'padre_id')->orderBy('orden');
    }
}
