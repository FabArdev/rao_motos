<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConfiguracionController extends Controller
{
    public function index()
    {
        return Inertia::render('Configuracion/Index', [
            'parametros' => Configuracion::orderBy('clave')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'parametros' => ['required', 'array'],
            'parametros.*.id' => ['required', 'exists:configuracion,id'],
            'parametros.*.valor' => ['required', 'string', 'max:255'],
        ], [
            'parametros.*.valor.required' => 'Cada parámetro debe tener un valor.',
        ]);

        foreach ($data['parametros'] as $p) {
            Configuracion::where('id', $p['id'])->update(['valor' => $p['valor']]);
        }

        return back()->with('success', 'Parámetros actualizados.');
    }
}
