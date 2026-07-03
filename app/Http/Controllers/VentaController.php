<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVentaRequest;
use App\Models\Cliente;
use App\Models\Notificacion;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\CreditoService;
use App\Services\InventarioService;
use App\Services\VentaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class VentaController extends Controller
{
    public function __construct(
        private VentaService $ventas,
        private CreditoService $creditos,
        private InventarioService $inventario,
    ) {}

    public function index(Request $request)
    {
        $q = $request->string('q')->toString();
        $estado = $request->string('estado')->toString();

        // El almacenero trabaja el despacho: por defecto ve las ventas PAGADA (listas para despachar).
        if ($estado === '' && $request->user()->tieneRol('almacenero') && ! $request->has('estado')) {
            $estado = 'PAGADA';
        }

        $ventas = Venta::with(['cliente.user', 'vendedor', 'credito' => function ($c) {
            $c->withCount([
                'cuotas as cuotas_total',
                'cuotas as cuotas_pagadas' => fn ($q) => $q->where('estado', 'PAGADO'),
            ]);
        }])
            ->when($q, fn ($query) => $query->where('numero_venta', 'ilike', "%{$q}%"))
            ->when($estado, fn ($query) => $query->where('estado', $estado))
            ->latest('fecha')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Ventas/Index', [
            'ventas' => $ventas,
            'filtros' => ['q' => $q, 'estado' => $estado],
        ]);
    }

    public function create()
    {
        return Inertia::render('Ventas/Create', [
            'clientes' => Cliente::with('user:id,nombre,apellidos')->get()
                ->map(fn ($c) => ['id' => $c->id, 'nombre' => trim(($c->user->nombre ?? '').' '.($c->user->apellidos ?? '')), 'nit_ci' => $c->nit_ci]),
            'productos' => Producto::with('inventario:id,producto_id,stock_actual')
                ->where('activo', true)->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre', 'precio_venta_base', 'precio_mayorista', 'cantidad_minima_mayorista']),
        ]);
    }

    public function store(StoreVentaRequest $request)
    {
        $data = $request->validated();

        try {
            $venta = DB::transaction(function () use ($data, $request) {
                // Contado en efectivo se completa al instante; QR queda PENDIENTE hasta confirmar PagoFácil.
                $estado = ($data['tipo_venta'] === 'CONTADO' && $data['metodo_pago'] === 'EFECTIVO')
                    ? 'COMPLETADA'
                    : ($data['tipo_venta'] === 'CREDITO' ? 'COMPLETADA' : 'PENDIENTE');

                $venta = $this->ventas->crear([
                    'cliente_id' => $data['cliente_id'],
                    'vendedor_id' => $request->user()->id,
                    'tipo_venta' => $data['tipo_venta'],
                    'metodo_pago' => $data['metodo_pago'],
                    'estado' => $estado,
                    'descontar_stock' => true,
                    'items' => $data['items'],
                ]);

                if ($data['tipo_venta'] === 'CREDITO') {
                    $this->creditos->generar($venta, (int) $data['numero_cuotas'], $data['tasa_interes'] ?? null);
                }

                return $venta;
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        // Venta al contado con QR: ir directo a la pantalla de cobro por QR (PagoFácil).
        if ($venta->tipo_venta === 'CONTADO' && $venta->metodo_pago === 'QR' && $venta->estado === 'PENDIENTE') {
            return redirect()->route('pagofacil.generar-qr-venta', $venta->id);
        }

        return redirect()->route('ventas.show', $venta->id)->with('success', "Venta {$venta->numero_venta} registrada.");
    }

    public function show(Venta $venta)
    {
        $venta->load(['cliente.user', 'vendedor', 'detalles.producto', 'credito.cuotas']);

        return Inertia::render('Ventas/Show', ['venta' => $venta]);
    }

    /** Anular venta: revierte stock si estaba descontado. */
    public function anular(Venta $venta)
    {
        if ($venta->estado === 'ANULADA') {
            return back()->with('error', 'La venta ya está anulada.');
        }
        if ($venta->credito) {
            return back()->with('error', 'No se puede anular una venta con crédito asociado.');
        }

        DB::transaction(function () use ($venta) {
            $venta->load('detalles');
            foreach ($venta->detalles as $d) {
                if ($d->producto_id) {
                    $this->inventario->ingreso($d->producto_id, $d->cantidad, "Anulación venta {$venta->numero_venta}");
                }
            }
            $venta->update(['estado' => 'ANULADA']);
        });

        return back()->with('success', 'Venta anulada y stock revertido.');
    }

    /** El vendedor confirma el cobro en efectivo de una venta pendiente → PAGADA (lista para despacho). */
    public function marcarPagada(Venta $venta)
    {
        if ($venta->estado !== 'PENDIENTE' || $venta->metodo_pago !== 'EFECTIVO') {
            return back()->with('error', 'Solo una venta PENDIENTE en efectivo puede marcarse como pagada.');
        }

        $venta->update(['estado' => 'PAGADA']);

        Notificacion::paraRol(
            'almacenero',
            'VENTA_PAGADA',
            "Venta {$venta->numero_venta} pagada, lista para despachar.",
            route('ventas.show', $venta->id, false)
        );

        return back()->with('success', "Venta {$venta->numero_venta} marcada como pagada. Se notificó al almacén.");
    }

    /** El almacenero despacha una venta PAGADA: descuenta stock (si viene de pedido) y la completa. */
    public function despachar(Venta $venta)
    {
        if ($venta->estado !== 'PAGADA') {
            return back()->with('error', 'Solo una venta PAGADA puede despacharse.');
        }

        $pedido = Pedido::where('venta_id', $venta->id)->first();

        try {
            DB::transaction(function () use ($venta, $pedido) {
                if ($pedido) {
                    // Venta originada en pedido: el stock aún no se descontó (RN18) → sale ahora.
                    $venta->load('detalles');
                    foreach ($venta->detalles as $d) {
                        if ($d->producto_id) {
                            $this->inventario->egreso($d->producto_id, $d->cantidad, "Despacho pedido #{$pedido->id} ({$venta->numero_venta})");
                        }
                    }
                    $pedido->update(['estado' => 'DESPACHADO']);
                }
                // Venta directa: el stock ya se descontó al crearla; solo completar.
                $venta->update(['estado' => 'COMPLETADA']);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($pedido) {
            Notificacion::create([
                'usuario_id' => $pedido->cliente_id,
                'tipo' => 'PEDIDO_DESPACHADO',
                'mensaje' => "Tu pedido #{$pedido->id} fue despachado.",
                'recurso' => route('mis-pedidos.show', $pedido->id, false),
                'leido' => false,
                'fecha' => now(),
            ]);
        }

        return back()->with('success', "Venta {$venta->numero_venta} despachada y completada.");
    }
}
