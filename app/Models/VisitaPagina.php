<?php

namespace App\Models;

class VisitaPagina extends ModeloBase
{
    protected $table = 'visita_pagina';

    protected $fillable = ['ruta', 'contador'];

    protected $casts = ['contador' => 'integer'];
}
