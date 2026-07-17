<?php

namespace App\Http\Controllers;

use App\Models\Credito;
use App\Models\MetodoPago;
use App\Models\PagoCuota;
use App\Services\CreditoService;
use App\Services\PagoFacilService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CreditoController extends Controller
{
    public function __construct(
        private CreditoService $creditos,
        private PagoFacilService $pagofacil,
    ) {}

    public function index(Request $request)
    {
        $estado = $request->string('estado')->toString();

        $creditos = Credito::with(['venta.cliente.usuario'])
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->latest('creado_en')
            ->paginate(12)
            ->withQueryString();

        $resumen = [
            'vigentes' => Credito::where('estado', 'VIGENTE')->count(),
            'morosos' => Credito::where('estado', 'MOROSO')->count(),
            'pagados' => Credito::where('estado', 'PAGADO')->count(),
        ];

        return Inertia::render('Creditos/Index', [
            'creditos' => $creditos,
            'filtros' => ['estado' => $estado],
            'resumen' => $resumen,
        ]);
    }

    public function show(Credito $credito)
    {
        $credito->load(['venta.cliente.usuario', 'cuotas' => fn ($q) => $q->orderBy('numero_cuota')]);

        $this->verificarCuotasPendientes($credito);
        $credito->load(['cuotas' => fn ($q) => $q->orderBy('numero_cuota')]);

        // Mora calculada al momento para las cuotas aún pendientes/vencidas.
        $cuotas = $credito->cuotas->map(function (PagoCuota $c) {
            $arr = $c->toArray();
            $arr['mora_actual'] = $c->estado === 'PAGADO' ? (float) $c->mora : $this->creditos->calcularMora($c);

            return $arr;
        });

        return Inertia::render('Creditos/Show', [
            'credito' => $credito,
            'cuotas' => $cuotas,
            'metodosPago' => MetodoPago::where('activo', true)->get(['id', 'nombre']),
        ]);
    }

    /**
     * Revisa cuotas con QR pendiente contra PagoFácil y las marca pagadas si corresponde.
     */
    private function verificarCuotasPendientes(Credito $credito): void
    {
        foreach ($credito->cuotas as $cuota) {
            if ($cuota->estado === 'PAGADO' || ! $cuota->pago_facil_id_transaccion) {
                continue;
            }

            $resultado = $this->pagofacil->verificarEstadoPago(
                $cuota->pago_facil_id_transaccion,
                $cuota->pago_facil_numero_pago
            );

            $raw = $resultado['raw'] ?? [];

            $pagado = ($resultado['status'] ?? 'pending') === 'completed'
                || ! empty($raw['payerName']);

            if ($pagado) {
                $cuota->update(['pago_facil_estado' => 'completed']);
                $this->creditos->registrarPagoCuota($cuota, $cuota->metodo_pago_id);

                Log::info('✅ [PagoFácil] Verificación desde Creditos/Show detectó pago', [
                    'cuota' => $cuota->id,
                    'payerName' => $raw['payerName'] ?? null,
                ]);
            }
        }
    }

    /** El vendedor registra el pago de una cuota (efectivo o QR ya confirmado). */
    public function pagarCuota(Request $request, PagoCuota $cuota)
    {
        $data = $request->validate([
            'metodo_pago_id' => ['nullable', 'exists:metodos_pago,id'],
        ]);

        if ($cuota->estado === 'PAGADO') {
            return back()->with('error', 'La cuota ya fue pagada.');
        }

        $this->creditos->registrarPagoCuota($cuota, $data['metodo_pago_id'] ?? null);

        return back()->with('success', "Cuota #{$cuota->numero_cuota} registrada como pagada.");
    }
}
