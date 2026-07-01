<?php

namespace App\Http\Middleware;

use App\Services\PageVisitService;
use Closure;
use Illuminate\Http\Request;

class TrackPageVisits
{
    public function __construct(protected PageVisitService $pageVisitService) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Solo registrar si el usuario está autenticado
        if (! auth()->check()) {
            return $next($request);
        }

        $ruta = $request->path();

        // Verificar si la ruta debe ser contabilizada
        if ($this->pageVisitService->debeContabilizar($ruta)) {
            $this->pageVisitService->registrarVisita($ruta);
        }

        return $next($request);
    }
}
