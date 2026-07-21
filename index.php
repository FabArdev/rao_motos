<?php

/*
|--------------------------------------------------------------------------
| Punto de entrada de RAO MOTOS
|--------------------------------------------------------------------------
|
| Este es el archivo que atiende TODAS las peticiones. Normalmente Laravel
| lo pone dentro de public/, pero en el servidor de la facultad la dirección
| del grupo apunta a la carpeta del proyecto, así que vive aquí para que el
| sitio se abra en .../proyecto2 y no en .../proyecto2/public.
|
| Las rutas son relativas a ESTA carpeta (sin el "/.." que llevaba la versión
| de public/). Los archivos estáticos (build, img, storage) siguen dentro de
| public/ y el .htaccess de al lado los encamina; por eso public_path() NO se
| cambia — moverlo haría que `artisan storage:link` creara el enlace encima de
| la carpeta storage/ del framework.
|
| public/index.php sigue existiendo y solo llama a este archivo, para que el
| entorno local (Herd, que sirve public/) siga funcionando igual.
|
*/

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Modo mantenimiento (`artisan down`).
if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Autocargador de Composer.
require __DIR__.'/vendor/autoload.php';

// Arranque de la aplicación y atención de la petición.
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
