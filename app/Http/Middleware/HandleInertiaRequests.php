<?php

namespace App\Http\Middleware;

use App\Models\MenuItem;
use App\Models\Notificacion;
use App\Models\PageVisit;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Props compartidos en cada página.
     * (Permisos por recurso se reincorporan en la fase de CU con las policies RAO.)
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? array_merge(
                    $request->user()->toArray(),
                    [
                        'rol' => $request->user()->role?->nombre,
                        'profile_photo_url' => $request->user()->profile_photo_url ?? null,
                    ]
                ) : null,
            ],

            // Menú dinámico filtrado por rol (datos; las rutas se cablean por CU)
            'menuItems' => function () use ($request) {
                $user = $request->user();
                if (! $user || ! $user->role_id) {
                    return [];
                }

                return MenuItem::where('role_id', $user->role_id)
                    ->where('activo', true)
                    ->orderBy('orden')
                    ->get(['etiqueta', 'ruta_laravel', 'icono', 'parent_id']);
            },

            // Notificaciones in-app (badge + dropdown en el navbar)
            'notificaciones' => function () use ($request) {
                $user = $request->user();
                if (! $user) {
                    return ['no_leidas' => 0, 'recientes' => []];
                }

                return [
                    'no_leidas' => Notificacion::where('usuario_id', $user->id)->where('leido', false)->count(),
                    'recientes' => Notificacion::where('usuario_id', $user->id)
                        ->latest('fecha')->limit(6)
                        ->get(['id', 'tipo', 'mensaje', 'recurso', 'leido', 'fecha']),
                ];
            },

            // Contador de visitas por página (REQ7) — cada ruta tiene su propio contador.
            'visitas' => fn () => (int) PageVisit::where('ruta', $request->path())->value('contador') ?? 0,

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
