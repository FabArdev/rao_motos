<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use App\Models\Pedido;
use App\Models\Venta;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Despachos (logística del almacén). Separación de funciones: el vendedor cobra,
 * el almacenero despacha. Solo se despachan ventas ya PAGADA (RN20/RN21).
 */
class DespachoController extends Controller
{
    public function __construct(private InventarioService $inventario) {}

    /** Cola de despacho: ventas pagadas, listas para preparar y entregar. */
    public function index(Request $request)
    {
        $q = $request->string('q')->toString();

        $ventas = Venta::with('cliente.user')
            ->withCount('detalles')
            ->where('estado', 'PAGADA')
            ->when($q, fn ($query) => $query->where('numero_venta', 'ilike', "%{$q}%"))
            ->latest('fecha')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Despachos/Index', ['ventas' => $ventas, 'filtros' => ['q' => $q]]);
    }

    /** Detalle de lo que hay que preparar (productos, cantidades, stock). */
    public function show(Venta $venta)
    {
        $venta->load(['cliente.user', 'vendedor', 'detalles.producto.inventario']);

        return Inertia::render('Despachos/Show', ['venta' => $venta]);
    }

    /** Despacha una venta PAGADA: descuenta stock (si viene de pedido) y la completa. */
    public function despachar(Venta $venta)
    {
        if ($venta->estado !== 'PAGADA') {
            return back()->with('error', 'Solo una venta PAGADA puede despacharse.');
        }

        $pedido = Pedido::where('venta_id', $venta->id)->first();

        try {
            DB::transaction(function () use ($venta, $pedido) {
                if ($pedido) {
                    // Venta originada en pedido: el stock aún no se descontó (RN18) → sale ahora.
                    $venta->load('detalles');
                    foreach ($venta->detalles as $d) {
                        if ($d->producto_id) {
                            $this->inventario->egreso($d->producto_id, $d->cantidad, "Despacho pedido #{$pedido->id} ({$venta->numero_venta})");
                        }
                    }
                    $pedido->update(['estado' => 'DESPACHADO']);
                }
                // Venta directa: el stock ya se descontó al crearla; solo completar.
                $venta->update(['estado' => 'COMPLETADA']);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($pedido) {
            Notificacion::create([
                'usuario_id' => $pedido->cliente_id,
                'tipo' => 'PEDIDO_DESPACHADO',
                'mensaje' => "Tu pedido #{$pedido->id} fue despachado.",
                'recurso' => route('mis-pedidos.show', $pedido->id, false),
                'leido' => false,
                'fecha' => now(),
            ]);
        }

        return redirect()->route('despachos.index')->with('success', "Venta {$venta->numero_venta} despachada y completada.");
    }
}
