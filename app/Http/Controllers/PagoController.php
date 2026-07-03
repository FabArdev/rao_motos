<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use App\Models\PagoCuota;
use App\Models\Venta;
use App\Services\CreditoService;
use App\Services\PagoFacilService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Pagos electrónicos por QR (PagoFácil) — REQ10 / RN13.
 * Genera el QR de una cuota, guarda la transacción en la BD y la confirma por callback.
 */
class PagoController extends Controller
{
    public function __construct(
        private PagoFacilService $pagofacil,
        private CreditoService $creditos,
    ) {}

    /** Genera el QR para pagar una cuota (cliente propio, o admin/vendedor). */
    public function generarQrCuota(Request $request, PagoCuota $cuota)
    {
        $cuota->load('credito.venta');
        $esAdminOVendedor = $request->user()?->tieneRol('admin') || $request->user()?->tieneRol('vendedor');
        if (!$esAdminOVendedor && $cuota->credito?->venta?->cliente_id !== $request->user()->id) {
            throw new NotFoundHttpException;
        }
        if ($cuota->estado === 'PAGADO') {
            $route = $esAdminOVendedor ? 'creditos.show' : 'mis-creditos.show';
            return redirect()->route($route, $cuota->credito_id)->with('error', 'La cuota ya fue pagada.');
        }

        $monto = (float) $cuota->monto_cuota + (float) $this->creditos->calcularMora($cuota);

        $metodoQr = MetodoPago::where('nombre', 'QR')->value('id');

        // Si ya hay un QR pendiente y no ha expirado, reusarlo
        // Si no tiene expires_at (QR previo a la migración), se considera expirado
        $qrExpirado = !$cuota->pago_facil_expires_at || Carbon::parse($cuota->pago_facil_expires_at)->isPast();
        if ($cuota->pago_facil_transaction_id && $cuota->pago_facil_qr_image && $cuota->pago_facil_status === 'pending' && !$qrExpirado) {
            $qrExistente = [
                'success' => true,
                'transaction_id' => $cuota->pago_facil_transaction_id,
                'payment_number' => $cuota->pago_facil_payment_number,
                'qr_image' => $cuota->pago_facil_qr_image,
                'status' => 'pending',
                'monto' => $monto,
                'glosa' => "Pago cuota #{$cuota->numero_cuota}",
                // Un QR real es data-URI base64; el fallback simulado es una URL http externa.
                'simulado' => ! str_starts_with((string) $cuota->pago_facil_qr_image, 'data:'),
            ];

            return $this->renderQrCuota($cuota, $monto, $qrExistente, $esAdminOVendedor);
        }

        try {
            $qr = $this->pagofacil->generarQRCuotaSimulado($cuota->id, $monto, "Pago cuota #{$cuota->numero_cuota}");

            $cuota->update([
                'metodo_pago_id' => $metodoQr,
                'pago_facil_transaction_id' => $qr['transaction_id'] ?? null,
                'pago_facil_payment_number' => $qr['payment_number'] ?? null,
                'pago_facil_qr_image' => $qr['qr_image'] ?? null,
                'pago_facil_expires_at' => isset($qr['expiration']) ? Carbon::parse($qr['expiration']) : null,
                'pago_facil_status' => $qr['status'] ?? 'pending',
            ]);

            return $this->renderQrCuota($cuota, $monto, $qr, $esAdminOVendedor);
        } catch (\Throwable $e) {
            Log::error('PagoFácil QR cuota falló', ['cuota' => $cuota->id, 'error' => $e->getMessage()]);

            $redirectRoute = $esAdminOVendedor ? 'creditos.show' : 'mis-creditos.show';

            return redirect()->route($redirectRoute, $cuota->credito_id)
                ->with('error', 'No se pudo generar el QR (verifique la conexión con PagoFácil). Intente el pago en caja.');
        }
    }

    /**
     * Callback de PagoFácil (notificación de pago) + confirmación manual (admin/vendedor).
     *
     * PagoFácil envía POST con:
     *   { "PedidoID": "CUOTA-XX-...", "Fecha": "...", "Hora": "...",
     *     "MetodoPago": "QR", "Estado": "Pagado" }
     *
     * El botón "Ya realicé el pago" envía:
     *   { "payment_number": "...", "transaction_id": "..." }
     *
     * Sin auth: PagoFácil llama por servidor. Idempotente.
     */
    public function confirmarCuota(Request $request)
    {
        Log::info('📩 [PagoFácil] Callback recibido', ['payload' => $request->all(), 'query' => $request->query()]);

        // Formato callback de PagoFácil → PedidoID
        $pedidoId = $request->input('PedidoID') ?? $request->input('pedidoId') ?? $request->input('pedido_id');

        // Formato del botón "Ya realicé el pago"
        $paymentNumber = $pedidoId
            ?? $request->input('paymentNumber')
            ?? $request->input('payment_number')
            ?? $request->input('paymentnumber')
            ?? $request->query('paymentNumber')
            ?? $request->query('payment_number');

        $transactionId = $request->input('pagofacilTransactionId')
            ?? $request->input('pagofacil_transaction_id')
            ?? $request->input('transaction_id')
            ?? $request->input('transactionId')
            ?? $request->query('pagofacilTransactionId');

        // Sin identificador no se debe consultar: un WHERE vacío devolvería la primera cuota
        // y la marcaría pagada sin cobro real (el endpoint es público/CSRF-exento).
        if (! $paymentNumber && ! $transactionId) {
            return response()->json(['error' => 1, 'status' => 0, 'message' => 'Identificador de pago requerido', 'values' => false], 422);
        }

        $cuota = PagoCuota::when($paymentNumber, fn ($q) => $q->where('pago_facil_payment_number', $paymentNumber))
            ->when(! $paymentNumber && $transactionId, fn ($q) => $q->where('pago_facil_transaction_id', $transactionId))
            ->first();

        if (! $cuota) {
            Log::warning('⚠️ [PagoFácil] Callback: cuota no encontrada', [
                'paymentNumber' => $paymentNumber, 'transactionId' => $transactionId,
            ]);

            return response()->json(['error' => 1, 'status' => 0, 'message' => 'Cuota no encontrada', 'values' => false], 404);
        }
        if ($cuota->estado === 'PAGADO') {
            return response()->json(['error' => 0, 'status' => 1, 'message' => 'Ya estaba pagada', 'values' => true]);
        }

        $cuota->update(['pago_facil_status' => 'completed']);
        $this->creditos->registrarPagoCuota($cuota, $cuota->metodo_pago_id);

        Log::info('✅ [PagoFácil] Callback: cuota pagada', ['cuota' => $cuota->id]);

        return response()->json(['error' => 0, 'status' => 1, 'message' => 'Pago recibido correctamente', 'values' => true]);
    }

    /**
     * Consulta el estado de una cuota (para polling desde la página QR).
     * Consulta a PagoFácil API en tiempo real si la cuota tiene transaction_id.
     */
    public function estadoCuota(PagoCuota $cuota)
    {
        if ($cuota->estado === 'PAGADO') {
            return response()->json(['pagado' => true, 'estado' => 'PAGADO']);
        }

        $transactionId = $cuota->pago_facil_transaction_id;
        $paymentNumber = $cuota->pago_facil_payment_number;

        if ($transactionId) {
            $resultado = $this->pagofacil->verificarEstadoPago($transactionId, $paymentNumber);

            $raw = $resultado['raw'] ?? [];

            // PagoFácil deja paymentStatus=1 ("En Proceso") incluso después de pagar.
            // Detectamos pago real cuando payerName aparece (la app bancaria lo envió).
            $pagadoEnPagoFacil = ($resultado['status'] ?? 'pending') === 'completed'
                || (! empty($raw['payerName']));

            if ($pagadoEnPagoFacil) {
                $cuota->update(['pago_facil_status' => 'completed']);
                $this->creditos->registrarPagoCuota($cuota, $cuota->metodo_pago_id);

                Log::info('✅ [PagoFácil] Polling detectó pago completado', ['cuota' => $cuota->id, 'payerName' => $raw['payerName'] ?? null]);

                return response()->json(['pagado' => true, 'estado' => 'PAGADO']);
            }

            return response()->json([
                'pagado' => false,
                'estado' => $cuota->estado,
                'pago_facil_status' => $resultado['status'] ?? $cuota->pago_facil_status,
            ]);
        }

        return response()->json([
            'pagado' => false,
            'estado' => $cuota->estado,
            'pago_facil_status' => $cuota->pago_facil_status,
        ]);
    }

    /** Render de la pantalla QR para una cuota (props genéricos de Pagos/Qr). */
    private function renderQrCuota(PagoCuota $cuota, float $monto, array $qr, bool $esAdminOVendedor)
    {
        $volver = $esAdminOVendedor
            ? route('creditos.show', $cuota->credito_id)
            : route('mis-creditos.show', $cuota->credito_id);

        return Inertia::render('Pagos/Qr', [
            'titulo' => "Cuota #{$cuota->numero_cuota}",
            'monto' => $monto,
            'qr' => $qr,
            'estadoUrl' => route('pagofacil.estado-cuota', $cuota->id),
            'confirmarUrl' => route('pagofacil.confirmar-cuota'),
            'volverUrl' => $volver,
            'descarga' => "qr-pago-cuota-{$cuota->id}.png",
        ]);
    }

    /* =====================================================================
     |  Pago por QR de una VENTA al contado (caja) — mismo flujo que cuota.
     * ===================================================================== */

    /** Genera (o reutiliza) el QR para cobrar una venta al contado. */
    public function generarQrVenta(Venta $venta)
    {
        if ($venta->estado === 'COMPLETADA') {
            return redirect()->route('ventas.show', $venta->id)->with('error', 'La venta ya fue pagada.');
        }
        if ($venta->estado === 'ANULADA') {
            return redirect()->route('ventas.show', $venta->id)->with('error', 'La venta está anulada.');
        }

        $monto = (float) $venta->monto_total;

        // Reutilizar un QR pendiente si ya existe (evita crear una transacción por recarga).
        if ($venta->pago_facil_transaction_id && $venta->pago_facil_qr_image && $venta->pago_facil_status === 'pending') {
            $qrExistente = [
                'success' => true,
                'transaction_id' => $venta->pago_facil_transaction_id,
                'payment_number' => $venta->pago_facil_payment_number,
                'qr_image' => $venta->pago_facil_qr_image,
                'status' => 'pending',
                // Un QR real es data-URI base64; el fallback simulado es una URL http externa.
                'simulado' => ! str_starts_with((string) $venta->pago_facil_qr_image, 'data:'),
            ];

            return $this->renderQrVenta($venta, $monto, $qrExistente);
        }

        try {
            $qr = $this->pagofacil->generarQRVentaSimulado($venta->id, $monto, "Venta {$venta->numero_venta}");

            $venta->update([
                'pago_facil_transaction_id' => $qr['transaction_id'] ?? null,
                'pago_facil_payment_number' => $qr['payment_number'] ?? null,
                'pago_facil_qr_image' => $qr['qr_image'] ?? null,
                'pago_facil_status' => $qr['status'] ?? 'pending',
            ]);

            return $this->renderQrVenta($venta, $monto, $qr);
        } catch (\Throwable $e) {
            Log::error('PagoFácil QR venta falló', ['venta' => $venta->id, 'error' => $e->getMessage()]);

            return redirect()->route('ventas.show', $venta->id)
                ->with('error', 'No se pudo generar el QR (verifique la conexión con PagoFácil). Cobre en efectivo.');
        }
    }

    /** Estado de la venta para el polling de la pantalla QR. */
    public function estadoVenta(Venta $venta)
    {
        if ($venta->estado === 'COMPLETADA') {
            return response()->json(['pagado' => true, 'estado' => 'COMPLETADA']);
        }

        if ($venta->pago_facil_transaction_id) {
            $resultado = $this->pagofacil->verificarEstadoPago($venta->pago_facil_transaction_id, $venta->pago_facil_payment_number);
            $raw = $resultado['raw'] ?? [];
            $pagado = ($resultado['status'] ?? 'pending') === 'completed' || ! empty($raw['payerName']);

            if ($pagado) {
                $this->completarVenta($venta);

                return response()->json(['pagado' => true, 'estado' => 'COMPLETADA']);
            }
        }

        return response()->json(['pagado' => false, 'estado' => $venta->estado]);
    }

    /** Callback de PagoFácil (o botón "Ya realicé el pago") para una venta. Idempotente. */
    public function confirmarVenta(Request $request)
    {
        Log::info('📩 [PagoFácil] Callback venta recibido', ['payload' => $request->all(), 'query' => $request->query()]);

        $pedidoId = $request->input('PedidoID') ?? $request->input('pedidoId') ?? $request->input('pedido_id');
        $paymentNumber = $pedidoId
            ?? $request->input('paymentNumber') ?? $request->input('payment_number') ?? $request->query('payment_number');
        $transactionId = $request->input('pagofacilTransactionId')
            ?? $request->input('transaction_id') ?? $request->input('transactionId') ?? $request->query('transaction_id');

        // Sin identificador no se debe consultar (evita marcar una venta arbitraria como pagada).
        if (! $paymentNumber && ! $transactionId) {
            return response()->json(['error' => 1, 'status' => 0, 'message' => 'Identificador de pago requerido', 'values' => false], 422);
        }

        $venta = Venta::when($paymentNumber, fn ($q) => $q->where('pago_facil_payment_number', $paymentNumber))
            ->when(! $paymentNumber && $transactionId, fn ($q) => $q->where('pago_facil_transaction_id', $transactionId))
            ->first();

        if (! $venta) {
            return response()->json(['error' => 1, 'status' => 0, 'message' => 'Venta no encontrada', 'values' => false], 404);
        }
        if ($venta->estado === 'COMPLETADA') {
            return response()->json(['error' => 0, 'status' => 1, 'message' => 'Ya estaba pagada', 'values' => true]);
        }

        $this->completarVenta($venta);
        Log::info('✅ [PagoFácil] Callback: venta pagada', ['venta' => $venta->id]);

        return response()->json(['error' => 0, 'status' => 1, 'message' => 'Pago recibido correctamente', 'values' => true]);
    }

    /** Marca una venta como pagada por QR. El stock ya se descontó al crearla (RN18). */
    private function completarVenta(Venta $venta): void
    {
        $venta->update(['estado' => 'COMPLETADA', 'pago_facil_status' => 'completed']);
    }

    private function renderQrVenta(Venta $venta, float $monto, array $qr)
    {
        return Inertia::render('Pagos/Qr', [
            'titulo' => "Venta {$venta->numero_venta}",
            'monto' => $monto,
            'qr' => $qr,
            'estadoUrl' => route('pagofacil.estado-venta', $venta->id),
            'confirmarUrl' => route('pagofacil.confirmar-venta'),
            'volverUrl' => route('ventas.show', $venta->id),
            'descarga' => "qr-venta-{$venta->id}.png",
        ]);
    }
}
