<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  VisitaPagina — Contador de visitas por ruta
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Guarda cuántas veces se visitó cada página. Es la tabla detrás
 *  del contador de visitas del pie de página.
 *
 *  IMPLEMENTACIÓN
 *  - Tabla: visita_pagina. Extiende ModeloBase. Requisito REQ7.
 *  - Campos: ruta, contador.
 *  - La llena VisitaPaginaService desde un middleware.
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Models;

class VisitaPagina extends ModeloBase
{
    protected $table = 'visita_pagina';

    protected $fillable = ['ruta', 'contador'];

    protected $casts = ['contador' => 'integer'];
}
