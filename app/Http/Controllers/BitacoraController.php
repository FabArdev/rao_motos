<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BitacoraController extends Controller
{
    public function index(Request $request)
    {
        $accion = $request->string('accion')->toString();
        $q = $request->string('q')->toString();

        $registros = Bitacora::with('usuario:id,nombre,apellidos')
            ->when($accion, fn ($query) => $query->where('accion', $accion))
            ->when($q, fn ($query) => $query->where(fn ($s) => $s->where('correo', 'ilike', "%{$q}%")->orWhere('recurso', 'ilike', "%{$q}%")))
            ->latest('fecha')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Bitacora/Index', [
            'registros' => $registros,
            'filtros' => ['accion' => $accion, 'q' => $q],
        ]);
    }
}
