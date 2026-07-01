<?php

namespace App\Http\Controllers;

use App\Models\Credito;
use App\Models\MetodoPago;
use App\Models\PagoCuota;
use App\Services\CreditoService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CreditoController extends Controller
{
    public function __construct(private CreditoService $creditos) {}

    public function index(Request $request)
    {
        $estado = $request->string('estado')->toString();

        $creditos = Credito::with(['venta.cliente.user'])
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->latest()
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
        $credito->load(['venta.cliente.user', 'cuotas' => fn ($q) => $q->orderBy('numero_cuota')]);

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
