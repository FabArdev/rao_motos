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
     * Aprobar (vendedor): genera la venta PENDIENTE con el método de pago elegido.
     * No descuenta stock (eso pasa al despachar el almacenero). El pago ocurre ANTES del despacho.
     */
    public function aprobar(Request $request, Pedido $pedido)
    {
        $data = $request->validate([
            'metodo_pago' => ['required', 'in:EFECTIVO,QR'],
        ], [
            'metodo_pago.required' => 'Seleccione el método de pago.',
            'metodo_pago.in' => 'Método de pago inválido.',
        ]);

        if ($pedido->estado !== 'SOLICITADO') {
            return back()->with('error', 'Solo un pedido SOLICITADO puede aprobarse.');
        }

        $venta = DB::transaction(function () use ($pedido, $request, $data) {
            $pedido->load('detalles');

            $venta = $this->ventas->crear([
                'cliente_id' => $pedido->cliente_id,
                'vendedor_id' => $request->user()->id,
                'tipo_venta' => 'CONTADO',
                'metodo_pago' => $data['metodo_pago'],
                'estado' => 'PENDIENTE',
                'descontar_stock' => false, // el stock sale al despachar
                'items' => $pedido->detalles->map(fn ($d) => [
                    'producto_id' => $d->producto_id,
                    'cantidad' => $d->cantidad,
                ])->all(),
            ]);

            $pedido->update(['estado' => 'APROBADO', 'venta_id' => $venta->id]);

            return $venta;
        });

        $this->notificarCliente($pedido, 'PEDIDO_APROBADO', "Tu pedido #{$pedido->id} fue aprobado. Realiza el pago para que sea despachado.");

        // Con QR se cobra al instante; con efectivo, el vendedor marca "Cobrado" cuando el cliente pague.
        if ($data['metodo_pago'] === 'QR') {
            return redirect()->route('pagofacil.generar-qr-venta', $venta->id);
        }

        return back()->with('success', 'Pedido aprobado. Venta pendiente de cobro (efectivo).');
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
            'recurso' => route('mis-pedidos.show', $pedido->id, false),
            'leido' => false,
            'fecha' => now(),
        ]);
    }
}
