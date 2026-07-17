<?php

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
