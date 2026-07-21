<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  MetodoPago — Forma de pago disponible
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  El catálogo de formas de pago (por ejemplo efectivo o QR) que
 *  se pueden usar y activar/desactivar.
 *
 *  IMPLEMENTACIÓN
 *  - Tabla: metodo_pago. Extiende ModeloBase.
 *  - Campos: nombre, activo.
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Models;

class MetodoPago extends ModeloBase
{
    protected $table = 'metodo_pago';

    protected $fillable = ['nombre', 'activo'];

    protected $casts = ['activo' => 'boolean'];
}
