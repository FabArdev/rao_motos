<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use App\Models\PagoCuota;
use App\Services\CreditoService;
use App\Services\PagoFacilService;
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

        try {
            $qr = $this->pagofacil->generarQRCuotaSimulado($cuota->id, $monto, "Pago cuota #{$cuota->numero_cuota}");

            $metodoQr = MetodoPago::where('nombre', 'QR')->value('id');
            $cuota->update([
                'metodo_pago_id' => $metodoQr,
                'pago_facil_transaction_id' => $qr['transaction_id'] ?? null,
                'pago_facil_payment_number' => $qr['payment_number'] ?? null,
                'pago_facil_qr_image' => $qr['qr_image'] ?? null,
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
     * Confirmación del pago (callback de PagoFácil o botón "ya pagué" de la demo).
     * Sin auth: PagoFácil llama por servidor. Idempotente.
     * Formato de callback de PagoFácil:
     *   { "error":0, "status":1, "message":"Pago realizado correctamente", "values":true }
     * Identifica la transacción por paymentNumber, pagofacilTransactionId o similar.
     */
    public function confirmarCuota(Request $request)
    {
        Log::info('📩 [PagoFácil] Callback recibido', ['payload' => $request->all(), 'query' => $request->query()]);

        $paymentNumber = $request->input('paymentNumber')
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

            return response()->json(['ok' => false, 'error' => 'Cuota no encontrada'], 404);
        }
        if ($cuota->estado === 'PAGADO') {
            return response()->json(['ok' => true, 'message' => 'Ya estaba pagada']);
        }

        $cuota->update(['pago_facil_status' => 'completed']);
        $this->creditos->registrarPagoCuota($cuota, $cuota->metodo_pago_id);

        Log::info('✅ [PagoFácil] Callback: cuota pagada', ['cuota' => $cuota->id]);

        return response()->json(['ok' => true]);
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

            if (($resultado['status'] ?? 'pending') === 'completed') {
                $cuota->update(['pago_facil_status' => 'completed']);
                $this->creditos->registrarPagoCuota($cuota, $cuota->metodo_pago_id);

                Log::info('✅ [PagoFácil] Polling detectó pago completado', ['cuota' => $cuota->id]);

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
