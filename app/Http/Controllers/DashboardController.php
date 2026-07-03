<?php

namespace App\Http\Controllers;

use App\Models\Credito;
use App\Models\DetalleVenta;
use App\Models\Inventario;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // El dashboard de estadísticas es solo para el administrador.
        // Cada otro rol entra directo a su módulo principal.
        $user = $request->user();
        if (! $user->esAdmin()) {
            return redirect()->route(match ($user->role?->nombre) {
                'vendedor' => 'ventas.index',
                'almacenero' => 'inventario.index',
                'cliente' => 'catalogo.index',
                default => 'profile.show',
            });
        }

        $stats = [
            'ventas_total' => (float) Venta::where('estado', 'COMPLETADA')->sum('monto_total'),
            'ventas_count' => Venta::count(),
            'creditos_vigentes' => Credito::where('estado', 'VIGENTE')->count(),
            'creditos_morosos' => Credito::where('estado', 'MOROSO')->count(),
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

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'ventasMes' => $ventasMes,
            'topProductos' => $topProductos,
        ]);
    }
}
