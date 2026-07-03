<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificacionController extends Controller
{
    public function index(Request $request)
    {
        $notificaciones = Notificacion::where('usuario_id', $request->user()->id)
            ->latest('fecha')
            ->paginate(20);

        // Prop 'lista' (no 'notificaciones') para no chocar con el prop compartido
        // 'notificaciones' del navbar (HandleInertiaRequests), que trae {no_leidas, recientes}.
        return Inertia::render('Notificaciones/Index', ['lista' => $notificaciones]);
    }

    public function marcarLeida(Request $request, Notificacion $notificacion)
    {
        abort_unless($notificacion->usuario_id === $request->user()->id, 403);
        $notificacion->update(['leido' => true]);

        return back();
    }

    public function marcarTodas(Request $request)
    {
        Notificacion::where('usuario_id', $request->user()->id)->where('leido', false)->update(['leido' => true]);

        return back()->with('success', 'Notificaciones marcadas como leídas.');
    }
}
