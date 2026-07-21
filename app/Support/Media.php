<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  Media — URL pública de un archivo subido (fotos, galería)
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Los archivos que sube el usuario (foto de producto, galería,
 *  foto de perfil) viven en storage/app/public. Para que el
 *  navegador los vea normalmente se usa `php artisan storage:link`,
 *  que crea el atajo public/storage. En el servidor de la facultad
 *  ese atajo muchas veces NO se puede crear (el hosting no permite
 *  enlaces simbólicos), y entonces todas las imágenes salen rotas
 *  aunque la dirección se vea bien.
 *
 *  Esta clase resuelve la dirección de cada archivo sola: si el
 *  atajo existe lo usa (es lo más rápido); si no existe, devuelve
 *  una dirección atendida por Laravel que lee el archivo y lo
 *  entrega. En los dos casos la dirección se arma con asset()/route(),
 *  así que respeta el subdirectorio del despliegue
 *  (…/grupo02sa/proyecto2/public) y nunca queda una ruta suelta
 *  como "/storage/…" que en el servidor apuntaría al dominio raíz.
 *
 *  IMPLEMENTACIÓN
 *  - Tipo: clase de apoyo (App\Support), solo métodos estáticos.
 *  - url($ruta): dirección pública de una ruta relativa del disco
 *    'public' (ej. "productos/abc.jpg"). Devuelve null si no hay ruta.
 *  - hayEnlaceStorage(): comprueba una sola vez por petición si
 *    existe public/storage (se cachea en memoria).
 *  - La ruta de respaldo es 'media.mostrar' (routes/web.php).
 *  - La usan Producto, ProductoImagen y Usuario (foto de perfil).
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Support;

class Media
{
    protected static ?bool $hayEnlace = null;

    /** Dirección pública de un archivo del disco 'public'. */
    public static function url(?string $ruta): ?string
    {
        if (! $ruta) {
            return null;
        }

        // Si ya es una dirección completa (avatar externo, seed con URL), se respeta.
        if (str_starts_with($ruta, 'http://') || str_starts_with($ruta, 'https://') || str_starts_with($ruta, 'data:')) {
            return $ruta;
        }

        $ruta = ltrim(str_replace('\\', '/', $ruta), '/');

        return static::hayEnlaceStorage()
            ? asset('storage/'.$ruta)
            : route('media.mostrar', ['ruta' => $ruta]);
    }

    /** ¿Existe el atajo public/storage creado por `artisan storage:link`? */
    public static function hayEnlaceStorage(): bool
    {
        return static::$hayEnlace ??= file_exists(public_path('storage'));
    }
}
