<?php

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

    /** Convierte el recurso siempre a ruta relativa, para que funcione en cualquier entorno (local/producción). */
    public function getRecursoAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        if (!str_starts_with($value, 'http')) {
            return $value;
        }
        $components = parse_url($value);
        return ($components['path'] ?? '') . (isset($components['query']) ? '?' . $components['query'] : '');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /** Crea la misma notificación in-app para todos los usuarios de un rol. */
    public static function paraRol(string $rol, string $tipo, string $mensaje, ?string $recurso = null): void
    {
        $ids = User::whereHas('role', fn ($q) => $q->where('nombre', $rol))->pluck('id');
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
