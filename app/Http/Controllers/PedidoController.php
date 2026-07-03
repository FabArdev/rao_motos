<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use App\Models\Pedido;
use App\Services\VentaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PedidoController extends Controller
{
    public function __construct(
        private VentaService $ventas,
    ) {}

    public function index(Request $request)
    {
        $estado = $request->string('estado')->toString();

        $pedidos = Pedido::with(['cliente.user', 'venta:id,numero_venta,estado,metodo_pago'])
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

    /**
     * Aprobar (vendedor): SOLO aprueba/rechaza. Genera la venta PENDIENTE sin decidir el
     * método de pago — eso lo elige el CLIENTE después (QR en la app o efectivo en tienda).
     * No descuenta stock (eso pasa al despachar el almacenero).
     */
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
                'metodo_pago' => 'EFECTIVO', // provisional: lo define el pago (QR lo cambia; efectivo lo confirma el vendedor)
                'estado' => 'PENDIENTE',
                'descontar_stock' => false, // el stock sale al despachar
                'items' => $pedido->detalles->map(fn ($d) => [
                    'producto_id' => $d->producto_id,
                    'cantidad' => $d->cantidad,
                ])->all(),
            ]);

            $pedido->update(['estado' => 'APROBADO', 'venta_id' => $venta->id]);
        });

        $this->notificarCliente($pedido, 'PEDIDO_APROBADO', "Tu pedido #{$pedido->id} fue aprobado. Elige cómo pagar: QR en la app o efectivo en la tienda.");

        return back()->with('success', 'Pedido aprobado. El cliente elige cómo pagar (QR o efectivo).');
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

        $this->notificarCliente($pedido, 'PEDIDO_RECHAZADO', "Tu pedido #{$pedido->id} fue rechazado. Motivo: {$data['motivo_rechazo']}");

        return back()->with('success', 'Pedido rechazado.');
    }

    /** Aviso in-app al cliente dueño del pedido (usuario = cliente_id). */
    private function notificarCliente(Pedido $pedido, string $tipo, string $mensaje): void
    {
        Notificacion::create([
            'usuario_id' => $pedido->cliente_id,
            'tipo' => $tipo,
            'mensaje' => $mensaje,
            'recurso' => route('mis-pedidos.show', $pedido->id),
            'leido' => false,
            'fecha' => now(),
        ]);
    }
}
