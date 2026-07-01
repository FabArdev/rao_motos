<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProveedorRequest;
use App\Http\Requests\UpdateProveedorRequest;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProveedorController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->string('q')->toString();

        $proveedores = Proveedor::withCount('compras')
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('razon_social', 'ilike', "%{$q}%")
                        ->orWhere('contacto_principal', 'ilike', "%{$q}%")
                        ->orWhere('nit', 'ilike', "%{$q}%");
                });
            })
            ->orderBy('razon_social')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Proveedores/Index', [
            'proveedores' => $proveedores,
            'filtros' => ['q' => $q],
        ]);
    }

    public function create()
    {
        return Inertia::render('Proveedores/Create');
    }

    public function store(StoreProveedorRequest $request)
    {
        Proveedor::create($request->validated());

        return redirect()->route('proveedores.index')->with('success', 'Proveedor registrado correctamente.');
    }

    public function show(Proveedor $proveedor)
    {
        $proveedor->load(['compras' => fn ($q) => $q->latest('fecha')->limit(20)]);

        return Inertia::render('Proveedores/Show', ['proveedor' => $proveedor]);
    }

    public function edit(Proveedor $proveedor)
    {
        return Inertia::render('Proveedores/Edit', ['proveedor' => $proveedor]);
    }

    public function update(UpdateProveedorRequest $request, Proveedor $proveedor)
    {
        $proveedor->update($request->validated());

        return redirect()->route('proveedores.index')->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Proveedor $proveedor)
    {
        // Eliminación lógica: el proveedor puede estar referenciado por compras.
        $proveedor->update(['activo' => false]);

        return redirect()->route('proveedores.index')->with('success', 'Proveedor desactivado.');
    }
}
