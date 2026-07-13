<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompraRequest;
use App\Models\Compra;
use App\Models\Configuracion;
use App\Models\DetalleCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CompraController extends Controller
{
    public function __construct(private InventarioService $inventario) {}

    public function index(Request $request)
    {
        $estado = $request->string('estado')->toString();

        $compras = Compra::with('proveedor')
            ->withCount('detalles')
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->latest('fecha')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Compras/Index', [
            'compras' => $compras,
            'filtros' => ['estado' => $estado],
        ]);
    }

    public function create()
    {
        return Inertia::render('Compras/Create', [
            'proveedores' => Proveedor::where('activo', true)->orderBy('razon_social')->get(['id', 'razon_social']),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(['id', 'codigo', 'nombre', 'precio_venta_base']),
        ]);
    }

    public function store(StoreCompraRequest $request)
    {
        $data = $request->validated();

        $compra = DB::transaction(function () use ($data) {
            // El total lo calcula el servidor desde el detalle (RN11), nunca se teclea.
            $total = collect($data['detalles'])
                ->sum(fn ($d) => $d['cantidad'] * $d['precio_unitario']);

            $compra = Compra::create([
                'proveedor_id' => $data['proveedor_id'],
                'fecha' => now(),
                'total' => $total,
                'estado' => 'PENDIENTE',
            ]);

            foreach ($data['detalles'] as $d) {
                DetalleCompra::create([
                    'compra_id' => $compra->id,
                    'producto_id' => $d['producto_id'],
                    'cantidad' => $d['cantidad'],
                    'precio_unitario' => $d['precio_unitario'],
                ]);
            }

            return $compra;
        });

        return redirect()->route('compras.show', $compra->id)->with('success', 'Compra registrada en estado PENDIENTE.');
    }

    public function show(Compra $compra)
    {
        $compra->load(['proveedor', 'detalles.producto']);

        return Inertia::render('Compras/Show', ['compra' => $compra]);
    }

    /** Recibir la compra: cambia a RECIBIDA e ingresa el stock una sola vez (RN18). */
    public function recibir(Compra $compra)
    {
        if ($compra->estado !== 'PENDIENTE') {
            return back()->with('error', 'Solo una compra PENDIENTE puede recibirse.');
        }

        DB::transaction(function () use ($compra) {
            $compra->load('detalles');

            // Margenes de venta configurables por el admin (RN23). Con default sembrado (RN17).
            $margenMinorista = (float) Configuracion::valor('margen_venta_minorista', 25);
            $margenMayorista = (float) Configuracion::valor('margen_venta_mayorista', 15);

            foreach ($compra->detalles as $d) {
                $this->inventario->ingreso($d->producto_id, $d->cantidad, "Compra #{$compra->id} recibida");

                // Recalcula el precio de venta desde el costo de ESTA compra (último costo + margen).
                $costo = (float) $d->precio_unitario;
                Producto::whereKey($d->producto_id)->update([
                    'precio_venta_base' => round($costo * (1 + $margenMinorista / 100), 2),
                    'precio_mayorista' => round($costo * (1 + $margenMayorista / 100), 2),
                ]);
            }

            $compra->update(['estado' => 'RECIBIDA']);
        });

        return back()->with('success', 'Compra recibida: inventario y precios de venta actualizados.');
    }

    /** Anular: si ya estaba RECIBIDA revierte el ingreso de inventario (RN15). */
    public function anular(Compra $compra)
    {
        if ($compra->estado === 'ANULADA') {
            return back()->with('error', 'La compra ya está anulada.');
        }

        DB::transaction(function () use ($compra) {
            if ($compra->estado === 'RECIBIDA') {
                $compra->load('detalles');
                foreach ($compra->detalles as $d) {
                    // Revertir el ingreso = egreso equivalente.
                    $this->inventario->egreso($d->producto_id, $d->cantidad, "Anulación compra #{$compra->id}");
                }
            }
            $compra->update(['estado' => 'ANULADA']);
        });

        return back()->with('success', 'Compra anulada.');
    }

    public function destroy(Compra $compra)
    {
        if ($compra->estado === 'RECIBIDA') {
            return back()->with('error', 'No se puede eliminar una compra recibida; anúlela para revertir inventario.');
        }
        $compra->delete();

        return redirect()->route('compras.index')->with('success', 'Compra eliminada.');
    }
}
