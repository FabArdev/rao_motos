<?php

namespace App\Http\Middleware;

use App\Services\VisitaPaginaService;
use Closure;
use Illuminate\Http\Request;

class RegistrarVisitasPagina
{
    public function __construct(protected VisitaPaginaService $pageVisitService) {}

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
