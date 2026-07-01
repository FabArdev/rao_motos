<?php

namespace App\Http\Controllers;

use App\Models\Credito;
use App\Models\DetalleVenta;
use App\Models\Inventario;
use App\Models\OrdenTrabajo;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'ventas_total' => (float) Venta::where('estado', 'COMPLETADA')->sum('monto_total'),
            'ventas_count' => Venta::count(),
            'creditos_vigentes' => Credito::where('estado', 'VIGENTE')->count(),
            'creditos_morosos' => Credito::where('estado', 'MOROSO')->count(),
            'ordenes_abiertas' => OrdenTrabajo::whereNotIn('estado', ['ENTREGADA', 'CANCELADA'])->count(),
            'inventario_critico' => Inventario::whereColumn('stock_actual', '<', 'stock_minimo')
                ->whereHas('producto', fn ($p) => $p->where('activo', true))->count(),
        ];

        // Ventas por mes (últimos 6 meses), separando contado vs crédito.
        $ventasMes = Venta::where('estado', 'COMPLETADA')
            ->where('fecha', '>=', now()->subMonths(6)->startOfMonth())
            ->select(
                DB::raw("to_char(fecha, 'YYYY-MM') as mes"),
                'tipo_venta',
                DB::raw('sum(monto_total) as total')
            )
            ->groupBy('mes', 'tipo_venta')
            ->orderBy('mes')
            ->get();

        // Top 5 productos por cantidad vendida.
        $topProductos = DetalleVenta::whereNotNull('producto_id')
            ->select('producto_id', DB::raw('sum(cantidad) as total'))
            ->with('producto:id,nombre')
            ->groupBy('producto_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($d) => ['nombre' => $d->producto?->nombre ?? '—', 'total' => (int) $d->total]);

        // Órdenes de taller por estado.
        $ordenesEstado = OrdenTrabajo::select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')->get()
            ->map(fn ($o) => ['estado' => $o->estado, 'total' => (int) $o->total]);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'ventasMes' => $ventasMes,
            'topProductos' => $topProductos,
            'ordenesEstado' => $ordenesEstado,
        ]);
    }
}
