<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  Notificacion — Aviso dentro de la aplicación
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Son los avisos que aparecen en la campanita: "stock bajo",
 *  "pedido por aprobar", "venta pagada", etc. Cada aviso es para
 *  un usuario y puede llevar un enlace al recurso relacionado.
 *
 *  IMPLEMENTACIÓN
 *  - Tabla: notificacion. Extiende Model (sin timestamps propios,
 *    usa su columna 'fecha').
 *  - Accessor recurso: garantiza que el enlace sea URL absoluta
 *    (necesario para Inertia servido en subdirectorio).
 *  - Relación: usuario().
 *  - paraRol($rol, $tipo, $mensaje, $recurso): crea el mismo aviso
 *    para todos los usuarios de un rol.
 *  - Tipos: STOCK_BAJO, PEDIDO_POR_APROBAR, VENTA_PAGADA,
 *    PEDIDO_APROBADO, PEDIDO_RECHAZADO, PEDIDO_DESPACHADO, MORA.
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificacion';

    public $timestamps = false;

    protected $fillable = ['usuario_id', 'tipo', 'mensaje', 'recurso', 'leido', 'fecha'];

    protected $casts = [
        'leido' => 'boolean',
        'fecha' => 'datetime',
    ];

    public function getRecursoAttribute(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (str_starts_with($value, 'http')) {
            return $value;
        }

        return url($value);
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public static function paraRol(string $rol, string $tipo, string $mensaje, ?string $recurso = null): void
    {
        $ids = Usuario::whereHas('rol', fn ($q) => $q->where('nombre', $rol))->pluck('id');
        foreach ($ids as $usuarioId) {
            static::create([
                'usuario_id' => $usuarioId,
                'tipo' => $tipo,
                'mensaje' => $mensaje,
                'recurso' => $recurso,
                'leido' => false,
                'fecha' => now(),
            ]);
        }
    }
}
