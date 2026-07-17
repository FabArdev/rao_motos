<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use App\Models\Pedido;
use App\Services\PagoFacilService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MisPedidosController extends Controller
{
    public function __construct(private PagoFacilService $pagofacil) {}

    public function index(Request $request)
    {
        $pedidos = Pedido::with('venta')
            ->withCount('detalles')
            ->where('cliente_id', $request->user()->id)
            ->latest('fecha')
            ->paginate(12);

        return Inertia::render('MisPedidos/Index', ['pedidos' => $pedidos]);
    }

    public function show(Request $request, Pedido $pedido)
    {
        // Un cliente solo ve sus propios pedidos.
        if ($pedido->cliente_id !== $request->user()->id) {
            throw new NotFoundHttpException;
        }

        $pedido->load(['detalles.producto', 'venta']);

        return Inertia::render('MisPedidos/Show', ['pedido' => $pedido]);
    }

    /** El cliente paga por QR su pedido ya aprobado (venta PENDIENTE asociada). */
    public function pagarQr(Request $request, Pedido $pedido)
    {
        $this->autorizar($request, $pedido);
        $pedido->load('venta');
        $venta = $pedido->venta;

        if ($pedido->estado !== 'APROBADO' || ! $venta) {
            return redirect()->route('mis-pedidos.show', $pedido->id)->with('error', 'Este pedido aún no está disponible para pago.');
        }
        if (in_array($venta->estado, ['PAGADA', 'COMPLETADA'], true)) {
            return redirect()->route('mis-pedidos.show', $pedido->id)->with('error', 'Este pedido ya fue pagado.');
        }

        $monto = (float) $venta->monto_total;

        // Reutilizar un QR pendiente si ya existe.
        if ($venta->pago_facil_id_transaccion && $venta->pago_facil_imagen_qr && $venta->pago_facil_estado === 'pending') {
            $qr = [
                'success' => true,
                'transaction_id' => $venta->pago_facil_id_transaccion,
                'payment_number' => $venta->pago_facil_numero_pago,
                'qr_image' => $venta->pago_facil_imagen_qr,
                'status' => 'pending',
                'simulado' => ! str_starts_with((string) $venta->pago_facil_imagen_qr, 'data:'),
            ];
        } else {
            try {
                $qr = $this->pagofacil->generarQRVentaSimulado($venta->id, $monto, "Pedido #{$pedido->id}");
                $venta->update([
                    'metodo_pago' => 'QR',
                    'pago_facil_id_transaccion' => $qr['transaction_id'] ?? null,
                    'pago_facil_numero_pago' => $qr['payment_number'] ?? null,
                    'pago_facil_imagen_qr' => $qr['qr_image'] ?? null,
                    'pago_facil_estado' => $qr['status'] ?? 'pending',
                ]);
            } catch (\Throwable $e) {
                Log::error('PagoFácil QR pedido cliente falló', ['pedido' => $pedido->id, 'error' => $e->getMessage()]);

                return redirect()->route('mis-pedidos.show', $pedido->id)
                    ->with('error', 'No se pudo generar el QR. Intenta más tarde o paga en tienda.');
            }
        }

        return Inertia::render('Pagos/Qr', [
            'titulo' => "Pedido #{$pedido->id}",
            'monto' => $monto,
            'qr' => $qr,
            'estadoUrl' => route('mis-pedidos.estado-pago', $pedido->id),
            'volverUrl' => route('mis-pedidos.show', $pedido->id),
            'descarga' => "qr-pedido-{$pedido->id}.png",
        ]);
    }

    /** Polling del estado de pago del pedido (venta asociada) para la pantalla QR. */
    public function estadoPago(Request $request, Pedido $pedido)
    {
        $this->autorizar($request, $pedido);
        $pedido->load('venta');
        $venta = $pedido->venta;

        if (! $venta) {
            return response()->json(['pagado' => false]);
        }
        if (in_array($venta->estado, ['PAGADA', 'COMPLETADA'], true)) {
            return response()->json(['pagado' => true]);
        }

        if ($venta->pago_facil_id_transaccion) {
            $resultado = $this->pagofacil->verificarEstadoPago($venta->pago_facil_id_transaccion, $venta->pago_facil_numero_pago);
            $raw = $resultado['raw'] ?? [];
            $pagado = ($resultado['status'] ?? 'pending') === 'completed' || ! empty($raw['payerName']);

            if ($pagado) {
                // Pago recibido → PAGADA (falta que el almacén despache). Avisar al almacén.
                $venta->update(['estado' => 'PAGADA', 'pago_facil_estado' => 'completed']);
                Notificacion::paraRol(
                    'almacenero',
                    'VENTA_PAGADA',
                    "Venta {$venta->numero_venta} pagada, lista para despachar.",
                    route('despachos.show', $venta->id, false)
                );

                return response()->json(['pagado' => true]);
            }
        }

        return response()->json(['pagado' => false]);
    }

    private function autorizar(Request $request, Pedido $pedido): void
    {
        if ($pedido->cliente_id !== $request->user()->id) {
            throw new NotFoundHttpException;
        }
    }
}
