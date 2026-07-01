<?php

namespace App\Http\Controllers;

use App\Models\Credito;
use App\Models\PagoCuota;
use App\Services\CreditoService;
use App\Services\PagoFacilService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MisCreditosController extends Controller
{
    public function __construct(
        private CreditoService $creditos,
        private PagoFacilService $pagofacil,
    ) {}

    public function index(Request $request)
    {
        $creditos = Credito::with('venta')
            ->whereHas('venta', fn ($q) => $q->where('cliente_id', $request->user()->id))
            ->latest()
            ->paginate(12);

        return Inertia::render('MisCreditos/Index', ['creditos' => $creditos]);
    }

    public function show(Request $request, Credito $credito)
    {
        $credito->load(['venta', 'cuotas' => fn ($q) => $q->orderBy('numero_cuota')]);
        $this->autorizar($request, $credito);

        $this->verificarCuotasPendientes($credito);

        $cuotas = $credito->refresh()->cuotas->map(function (PagoCuota $c) {
            $arr = $c->toArray();
            $arr['mora_actual'] = $c->estado === 'PAGADO' ? (float) $c->mora : $this->creditos->calcularMora($c);

            return $arr;
        });

        return Inertia::render('MisCreditos/Show', ['credito' => $credito, 'cuotas' => $cuotas]);
    }

    /**
     * Pago de una cuota por el cliente. La integración QR (PagoFácil) se conecta
     * en la fase de pagos; por ahora registra el pago de la cuota propia.
     */
    public function pagar(Request $request, Credito $credito, PagoCuota $cuota)
    {
        $this->autorizar($request, $credito->load('venta'));

        if ($cuota->credito_id !== $credito->id) {
            throw new NotFoundHttpException;
        }
        if ($cuota->estado === 'PAGADO') {
            return back()->with('error', 'La cuota ya fue pagada.');
        }

        $this->creditos->registrarPagoCuota($cuota);

        return back()->with('success', "Cuota #{$cuota->numero_cuota} pagada correctamente.");
    }

    private function autorizar(Request $request, Credito $credito): void
    {
        if ($credito->venta?->cliente_id !== $request->user()->id) {
            throw new NotFoundHttpException;
        }
    }

    /**
     * Revisa cuotas con QR pendiente contra PagoFácil y las marca pagadas si corresponde.
     */
    private function verificarCuotasPendientes(Credito $credito): void
    {
        foreach ($credito->cuotas as $cuota) {
            if ($cuota->estado === 'PAGADO' || !$cuota->pago_facil_transaction_id) {
                continue;
            }

            $resultado = $this->pagofacil->verificarEstadoPago(
                $cuota->pago_facil_transaction_id,
                $cuota->pago_facil_payment_number
            );

            $raw = $resultado['raw'] ?? [];

            $pagado = ($resultado['status'] ?? 'pending') === 'completed'
                || !empty($raw['payerName']);

            if ($pagado) {
                $cuota->update(['pago_facil_status' => 'completed']);
                $this->creditos->registrarPagoCuota($cuota, $cuota->metodo_pago_id);

                Log::info('✅ [PagoFácil] Verificación desde MisCreditos detectó pago', [
                    'cuota' => $cuota->id,
                    'payerName' => $raw['payerName'] ?? null,
                ]);
            }
        }
    }
}
