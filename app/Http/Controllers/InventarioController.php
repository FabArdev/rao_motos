<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InventarioController extends Controller
{
    public function __construct(private InventarioService $inventario) {}

    public function index(Request $request)
    {
        $q = $request->string('q')->toString();
        $soloAlertas = $request->boolean('alertas');

        $inventarios = Inventario::with('producto')
            ->whereHas('producto', fn ($p) => $p->where('activo', true))
            ->when($q, function ($query) use ($q) {
                $query->whereHas('producto', function ($p) use ($q) {
                    $p->where('nombre', 'ilike', "%{$q}%")
                        ->orWhere('codigo', 'ilike', "%{$q}%");
                });
            })
            ->when($soloAlertas, fn ($query) => $query->whereColumn('stock_actual', '<', 'stock_minimo'))
            ->orderBy('stock_actual')
            ->paginate(15)
            ->withQueryString();

        $totalAlertas = Inventario::whereColumn('stock_actual', '<', 'stock_minimo')
            ->whereHas('producto', fn ($p) => $p->where('activo', true))
            ->count();

        return Inertia::render('Inventario/Index', [
            'inventarios' => $inventarios,
            'filtros' => ['q' => $q, 'alertas' => $soloAlertas],
            'totalAlertas' => $totalAlertas,
        ]);
    }

    public function show(Inventario $inventario)
    {
        $inventario->load(['producto', 'movimientos' => fn ($q) => $q->latest('fecha')->limit(50)]);

        return Inertia::render('Inventario/Show', ['inventario' => $inventario]);
    }

    /** Ajuste manual de stock (positivo = ingreso, negativo = egreso). */
    public function ajuste(Request $request, Inventario $inventario)
    {
        $data = $request->validate([
            'cantidad' => ['required', 'integer', 'not_in:0'],
            'motivo' => ['required', 'string', 'max:255'],
        ], [
            'cantidad.required' => 'Indique la cantidad a ajustar.',
            'cantidad.not_in' => 'La cantidad no puede ser 0.',
            'motivo.required' => 'Indique el motivo del ajuste.',
        ]);

        try {
            DB::transaction(function () use ($data, $inventario) {
                $motivo = 'Ajuste manual: '.$data['motivo'];
                if ($data['cantidad'] > 0) {
                    $this->inventario->ingreso($inventario->producto_id, $data['cantidad'], $motivo);
                } else {
                    $this->inventario->egreso($inventario->producto_id, abs($data['cantidad']), $motivo);
                }
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Stock ajustado correctamente.');
    }
}
