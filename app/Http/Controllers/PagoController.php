<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use App\Models\PagoCuota;
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
        $qrExpirado = $cuota->pago_facil_expires_at && Carbon::parse($cuota->pago_facil_expires_at)->isPast();
        if ($cuota->pago_facil_transaction_id && $cuota->pago_facil_qr_image && $cuota->pago_facil_status === 'pending' && !$qrExpirado) {
            $qrExistente = [
                'success' => true,
                'transaction_id' => $cuota->pago_facil_transaction_id,
                'payment_number' => $cuota->pago_facil_payment_number,
                'qr_image' => $cuota->pago_facil_qr_image,
                'status' => 'pending',
                'monto' => $monto,
                'glosa' => "Pago cuota #{$cuota->numero_cuota}",
            ];

            $redirectRoute = $esAdminOVendedor ? 'creditos.show' : 'mis-creditos.show';

            return Inertia::render('Pagos/Qr', [
                'cuota' => $cuota->only(['id', 'numero_cuota', 'monto_cuota', 'credito_id']),
                'monto' => $monto,
                'qr' => $qrExistente,
                'redirectRoute' => $redirectRoute,
                'redirectParams' => ['credito' => $cuota->credito_id],
            ]);
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

            $redirectRoute = $esAdminOVendedor ? 'creditos.show' : 'mis-creditos.show';

            return Inertia::render('Pagos/Qr', [
                'cuota' => $cuota->only(['id', 'numero_cuota', 'monto_cuota', 'credito_id']),
                'monto' => $monto,
                'qr' => $qr,
                'redirectRoute' => $redirectRoute,
                'redirectParams' => ['credito' => $cuota->credito_id],
            ]);
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
}
