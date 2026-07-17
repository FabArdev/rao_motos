<?php

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
