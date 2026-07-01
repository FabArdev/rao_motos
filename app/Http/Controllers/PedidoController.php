<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Venta;
use App\Services\InventarioService;
use App\Services\VentaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PedidoController extends Controller
{
    public function __construct(
        private VentaService $ventas,
        private InventarioService $inventario,
    ) {}

    public function index(Request $request)
    {
        $estado = $request->string('estado')->toString();

        $pedidos = Pedido::with(['cliente.user'])
            ->withCount('detalles')
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->latest('fecha')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Pedidos/Index', [
            'pedidos' => $pedidos,
            'filtros' => ['estado' => $estado],
        ]);
    }

    public function show(Pedido $pedido)
    {
        $pedido->load(['cliente.user', 'detalles.producto.inventario', 'venta']);

        return Inertia::render('Pedidos/Show', ['pedido' => $pedido]);
    }

    /** Aprobar: genera la venta (PENDIENTE) con precio mayorista por línea (RN4/D). Sin descontar stock aún. */
    public function aprobar(Request $request, Pedido $pedido)
    {
        if ($pedido->estado !== 'SOLICITADO') {
            return back()->with('error', 'Solo un pedido SOLICITADO puede aprobarse.');
        }

        DB::transaction(function () use ($pedido, $request) {
            $pedido->load('detalles');

            $venta = $this->ventas->crear([
                'cliente_id' => $pedido->cliente_id,
                'vendedor_id' => $request->user()->id,
                'tipo_venta' => 'CONTADO',
                'metodo_pago' => 'EFECTIVO',
                'estado' => 'PENDIENTE',
                'descontar_stock' => false, // el stock sale al despachar
                'items' => $pedido->detalles->map(fn ($d) => [
                    'producto_id' => $d->producto_id,
                    'cantidad' => $d->cantidad,
                ])->all(),
            ]);

            $pedido->update(['estado' => 'APROBADO', 'venta_id' => $venta->id]);
        });

        return back()->with('success', 'Pedido aprobado y venta generada (pendiente de despacho).');
    }

    public function rechazar(Request $request, Pedido $pedido)
    {
        $data = $request->validate([
            'motivo_rechazo' => ['required', 'string', 'max:255'],
        ], ['motivo_rechazo.required' => 'Indique el motivo del rechazo.']);

        if ($pedido->estado !== 'SOLICITADO') {
            return back()->with('error', 'Solo un pedido SOLICITADO puede rechazarse.');
        }

        $pedido->update(['estado' => 'RECHAZADO', 'motivo_rechazo' => $data['motivo_rechazo']]);

        return back()->with('success', 'Pedido rechazado.');
    }

    /** Despachar: descuenta el stock una sola vez (RN18) y completa la venta. */
    public function despachar(Pedido $pedido)
    {
        if ($pedido->estado !== 'APROBADO') {
            return back()->with('error', 'Solo un pedido APROBADO puede despacharse.');
        }

        try {
            DB::transaction(function () use ($pedido) {
                $venta = $pedido->venta;
                $venta->load('detalles');
                foreach ($venta->detalles as $d) {
                    if ($d->producto_id) {
                        $this->inventario->egreso($d->producto_id, $d->cantidad, "Despacho pedido #{$pedido->id} ({$venta->numero_venta})");
                    }
                }
                $venta->update(['estado' => 'COMPLETADA']);
                $pedido->update(['estado' => 'DESPACHADO']);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pedido despachado: stock descontado y venta completada.');
    }
}
