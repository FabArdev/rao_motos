<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\DetalleOrden;
use App\Models\Moto;
use App\Models\Notificacion;
use App\Models\OrdenTrabajo;
use App\Models\Producto;
use App\Models\User;
use App\Services\CreditoService;
use App\Services\InventarioService;
use App\Services\VentaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * CU9 — Taller. Flujo de reparación entre 4 actores (ALCANCE §CU9).
 * El taller no cobra: al terminar genera una venta normal reutilizando el
 * pipeline venta → credito → pago_cuota, sin volver a descontar stock (RN18).
 */
class TallerController extends Controller
{
    public function __construct(
        private VentaService $ventas,
        private CreditoService $creditos,
        private InventarioService $inventario,
    ) {}

    public function index(Request $request)
    {
        $estado = $request->string('estado')->toString();

        $ordenes = OrdenTrabajo::with(['cliente.user', 'moto'])
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->latest('fecha_ingreso')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Taller/Index', [
            'ordenes' => $ordenes,
            'filtros' => ['estado' => $estado],
        ]);
    }

    public function create()
    {
        return Inertia::render('Taller/Create', [
            'clientes' => Cliente::with('user:id,nombre,apellidos')->get()
                ->map(fn ($c) => ['id' => $c->id, 'nombre' => trim(($c->user->nombre ?? '').' '.($c->user->apellidos ?? ''))]),
            'motos' => Moto::get(['id', 'cliente_id', 'placa', 'marca', 'modelo']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente_id' => ['required', 'exists:cliente,id'],
            'moto_id' => ['nullable', 'exists:moto,id'],
            'nueva_moto.placa' => ['nullable', 'string', 'max:20'],
            'nueva_moto.marca' => ['nullable', 'string', 'max:100'],
            'nueva_moto.modelo' => ['nullable', 'string', 'max:100'],
            'nueva_moto.anio' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'descripcion_problema' => ['required', 'string'],
        ], [
            'cliente_id.required' => 'Seleccione el cliente.',
            'descripcion_problema.required' => 'Describa el problema de la moto.',
        ]);

        $orden = DB::transaction(function () use ($data) {
            $motoId = $data['moto_id'] ?? null;
            if (! $motoId) {
                $moto = Moto::create([
                    'cliente_id' => $data['cliente_id'],
                    'placa' => $data['nueva_moto']['placa'] ?? null,
                    'marca' => $data['nueva_moto']['marca'] ?? null,
                    'modelo' => $data['nueva_moto']['modelo'] ?? null,
                    'anio' => $data['nueva_moto']['anio'] ?? null,
                ]);
                $motoId = $moto->id;
            }

            return OrdenTrabajo::create([
                'cliente_id' => $data['cliente_id'],
                'moto_id' => $motoId,
                'fecha_ingreso' => now(),
                'descripcion_problema' => $data['descripcion_problema'],
                'estado' => 'RECIBIDA',
            ]);
        });

        return redirect()->route('taller.show', $orden->id)->with('success', 'Orden de trabajo registrada.');
    }

    public function show(OrdenTrabajo $orden)
    {
        $orden->load(['cliente.user', 'moto', 'detalles.producto', 'venta.credito']);

        return Inertia::render('Taller/Show', [
            'orden' => $orden,
            'productos' => Producto::with('inventario:id,producto_id,stock_actual')
                ->where('activo', true)->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre', 'precio_venta_base']),
        ]);
    }

    /** Mecánico: diagnostica y presupuesta → DIAGNOSTICADA. Avisa al cliente. */
    public function diagnosticar(Request $request, OrdenTrabajo $orden)
    {
        $data = $request->validate([
            'diagnostico' => ['required', 'string'],
            'costo_estimado_mano_obra' => ['required', 'numeric', 'min:0'],
            'costo_estimado_repuestos' => ['required', 'numeric', 'min:0'],
        ], [
            'diagnostico.required' => 'Ingrese el diagnóstico.',
            'costo_estimado_mano_obra.required' => 'Ingrese el costo estimado de mano de obra.',
            'costo_estimado_repuestos.required' => 'Ingrese el costo estimado de repuestos.',
        ]);

        if (! in_array($orden->estado, ['RECIBIDA', 'DIAGNOSTICADA'])) {
            return back()->with('error', 'La orden no está en un estado diagnosticable.');
        }

        $orden->update([
            'diagnostico' => $data['diagnostico'],
            'costo_estimado_mano_obra' => $data['costo_estimado_mano_obra'],
            'costo_estimado_repuestos' => $data['costo_estimado_repuestos'],
            'fecha_diagnostico' => now(),
            'estado' => 'DIAGNOSTICADA',
        ]);

        Notificacion::create([
            'usuario_id' => $orden->cliente_id,
            'tipo' => 'PRESUPUESTO',
            'mensaje' => 'El diagnóstico de tu moto está listo. Revisa y aprueba el presupuesto.',
            'recurso' => route('mi-taller.show', $orden->id, false),
            'leido' => false,
            'fecha' => now(),
        ]);

        return back()->with('success', 'Diagnóstico y presupuesto registrados. Se avisó al cliente.');
    }

    /** Mecánico: solicita repuestos (líneas SOLICITADO). Avisa al almacenero. */
    public function solicitarRepuestos(Request $request, OrdenTrabajo $orden)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'exists:producto,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ], [
            'items.required' => 'Agregue al menos un repuesto.',
        ]);

        if ($orden->estado !== 'EN_REPARACION') {
            return back()->with('error', 'Solo se solicitan repuestos en una orden EN_REPARACION.');
        }

        DB::transaction(function () use ($data, $orden) {
            foreach ($data['items'] as $it) {
                DetalleOrden::create([
                    'orden_trabajo_id' => $orden->id,
                    'producto_id' => $it['producto_id'],
                    'cantidad' => $it['cantidad'],
                    'estado' => 'SOLICITADO',
                ]);
            }

            $almaceneros = User::whereHas('role', fn ($r) => $r->where('nombre', 'almacenero'))->pluck('id');
            foreach ($almaceneros as $usuarioId) {
                Notificacion::create([
                    'usuario_id' => $usuarioId,
                    'tipo' => 'SOLICITUD_REPUESTO',
                    'mensaje' => "Solicitud de repuestos de la orden #{$orden->id} por aprobar.",
                    'recurso' => route('taller.show', $orden->id, false),
                    'leido' => false,
                    'fecha' => now(),
                ]);
            }
        });

        return back()->with('success', 'Repuestos solicitados al almacén.');
    }

    /** Almacenero: aprueba un repuesto (descuenta stock una sola vez, RN18) y lo entrega. */
    public function aprobarRepuesto(DetalleOrden $detalle)
    {
        if ($detalle->estado !== 'SOLICITADO') {
            return back()->with('error', 'El repuesto no está en estado SOLICITADO.');
        }

        try {
            DB::transaction(function () use ($detalle) {
                $this->inventario->egreso($detalle->producto_id, $detalle->cantidad, "Repuesto orden #{$detalle->orden_trabajo_id}");
                $detalle->update(['estado' => 'ENTREGADO']);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Repuesto aprobado y entregado (stock descontado).');
    }

    public function rechazarRepuesto(Request $request, DetalleOrden $detalle)
    {
        $data = $request->validate(['motivo' => ['nullable', 'string', 'max:255']]);

        if ($detalle->estado !== 'SOLICITADO') {
            return back()->with('error', 'El repuesto no está en estado SOLICITADO.');
        }

        $detalle->update(['estado' => 'RECHAZADO', 'motivo' => $data['motivo'] ?? null]);

        return back()->with('success', 'Repuesto rechazado.');
    }

    /** Mecánico: marca la orden terminada, fijando el costo real de mano de obra. */
    public function terminar(Request $request, OrdenTrabajo $orden)
    {
        $data = $request->validate([
            'costo_mano_obra' => ['required', 'numeric', 'min:0'],
        ], ['costo_mano_obra.required' => 'Ingrese el costo real de mano de obra.']);

        if ($orden->estado !== 'EN_REPARACION') {
            return back()->with('error', 'Solo una orden EN_REPARACION puede terminarse.');
        }

        $orden->update(['costo_mano_obra' => $data['costo_mano_obra'], 'estado' => 'TERMINADA']);

        return back()->with('success', 'Orden marcada como terminada. Lista para facturar.');
    }

    /** Vendedor: factura la orden terminada → genera venta (mano de obra + repuestos), sin re-descontar stock. */
    public function facturar(Request $request, OrdenTrabajo $orden)
    {
        $data = $request->validate([
            'tipo_venta' => ['required', 'in:CONTADO,CREDITO'],
            'metodo_pago' => ['required', 'in:EFECTIVO,QR'],
            'numero_cuotas' => ['required_if:tipo_venta,CREDITO', 'nullable', 'integer', 'min:2'],
            'tasa_interes' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        if ($orden->estado !== 'TERMINADA') {
            return back()->with('error', 'Solo una orden TERMINADA puede facturarse.');
        }
        if ($orden->venta_id) {
            return back()->with('error', 'La orden ya tiene una venta asociada.');
        }

        $venta = DB::transaction(function () use ($data, $orden, $request) {
            $orden->load('detalles.producto');

            // Líneas: repuestos entregados (producto, precio base) + mano de obra (servicio).
            $items = [];
            foreach ($orden->detalles->where('estado', 'ENTREGADO') as $d) {
                $items[] = [
                    'producto_id' => null, // ya salió del stock al aprobarse; va como línea de repuesto sin re-descontar
                    'descripcion' => 'Repuesto: '.($d->producto->nombre ?? "#{$d->producto_id}"),
                    'cantidad' => $d->cantidad,
                    'precio_unitario' => (float) ($d->producto->precio_venta_base ?? 0),
                ];
            }
            $items[] = [
                'producto_id' => null,
                'descripcion' => 'Mano de obra (taller)',
                'cantidad' => 1,
                'precio_unitario' => (float) $orden->costo_mano_obra,
            ];

            $estado = ($data['tipo_venta'] === 'CONTADO' && $data['metodo_pago'] === 'EFECTIVO') ? 'COMPLETADA'
                : ($data['tipo_venta'] === 'CREDITO' ? 'COMPLETADA' : 'PENDIENTE');

            $venta = $this->ventas->crear([
                'cliente_id' => $orden->cliente_id,
                'vendedor_id' => $request->user()->id,
                'tipo_venta' => $data['tipo_venta'],
                'metodo_pago' => $data['metodo_pago'],
                'estado' => $estado,
                'descontar_stock' => false, // el stock ya salió al aprobar los repuestos (RN18)
                'items' => $items,
            ]);

            if ($data['tipo_venta'] === 'CREDITO') {
                $this->creditos->generar($venta, (int) $data['numero_cuotas'], $data['tasa_interes'] ?? null);
            }

            $orden->update(['venta_id' => $venta->id]);

            return $venta;
        });

        return back()->with('success', "Orden facturada: venta {$venta->numero_venta} generada.");
    }

    /** Entrega final de la moto al cliente. */
    public function entregar(OrdenTrabajo $orden)
    {
        if ($orden->estado !== 'TERMINADA' || ! $orden->venta_id) {
            return back()->with('error', 'La orden debe estar TERMINADA y facturada para entregarse.');
        }

        $orden->update(['estado' => 'ENTREGADA']);

        return back()->with('success', 'Moto entregada al cliente.');
    }
}
