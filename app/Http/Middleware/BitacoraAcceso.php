<?php

namespace App\Http\Middleware;

use App\Models\Bitacora;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registra en bitácora el acceso a recursos del negocio (REQ4: ACCESO_RECURSO).
 * Solo navegaciones GET a rutas con nombre, para evitar ruido.
 */
class BitacoraAcceso
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $nombreRuta = $request->route()?->getName();

        if ($request->isMethod('GET') && $request->user() && $nombreRuta) {
            Bitacora::create([
                'usuario_id' => $request->user()->id,
                'correo' => $request->user()->correo,
                'accion' => 'ACCESO_RECURSO',
                'recurso' => $nombreRuta,
                'ip' => $request->ip(),
                'agente_usuario' => $request->userAgent(),
                'fecha' => now(),
            ]);
        }

        return $response;
    }
}
