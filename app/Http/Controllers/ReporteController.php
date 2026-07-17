<?php

namespace App\Http\Controllers;

use App\Models\Credito;
use App\Models\DetalleVenta;
use App\Models\Inventario;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReporteController extends Controller
{
    public function index()
    {
        return Inertia::render('Reportes/Index');
    }

    /** Reporte de ventas por rango de fechas (contado vs crédito). */
    public function ventas(Request $request)
    {
        $desde = $request->date('desde') ?? now()->subMonth();
        $hasta = $request->date('hasta') ?? now();

        $ventas = Venta::with(['cliente.usuario', 'vendedor'])
            ->whereBetween('fecha', [$desde->startOfDay(), $hasta->endOfDay()])
            ->where('estado', '!=', 'ANULADA')
            ->orderBy('fecha')
            ->get();

        $data = [
            'ventas' => $ventas,
            'desde' => $desde->format('d/m/Y'),
            'hasta' => $hasta->format('d/m/Y'),
            'total' => $ventas->sum('monto_total'),
            'contado' => $ventas->where('tipo_venta', 'CONTADO')->sum('monto_total'),
            'credito' => $ventas->where('tipo_venta', 'CREDITO')->sum('monto_total'),
        ];

        return Pdf::loadView('reportes.ventas', $data)->download('reporte-ventas.pdf');
    }

    /** Reporte de créditos por estado (vigentes / morosos / pagados). */
    public function creditos()
    {
        $creditos = Credito::with('venta.cliente.usuario')->orderBy('estado')->get();

        return Pdf::loadView('reportes.creditos', [
            'creditos' => $creditos,
            'saldoTotal' => $creditos->sum('saldo_pendiente'),
        ])->download('reporte-creditos.pdf');
    }

    /** Reporte de inventario crítico (stock bajo el mínimo). */
    public function inventario()
    {
        $items = Inventario::with('producto')
            ->whereColumn('stock_actual', '<', 'stock_minimo')
            ->whereHas('producto', fn ($p) => $p->where('activo', true))
            ->get();

        return Pdf::loadView('reportes.inventario', ['items' => $items])->download('reporte-inventario-critico.pdf');
    }

    /** Reporte de productos más vendidos. */
    public function topProductos()
    {
        $items = DetalleVenta::whereNotNull('producto_id')
            ->select('producto_id', DB::raw('sum(cantidad) as cantidad'), DB::raw('sum(cantidad * precio_unitario) as monto'))
            ->with('producto:id,codigo,nombre')
            ->groupBy('producto_id')
            ->orderByDesc('cantidad')
            ->limit(20)
            ->get();

        return Pdf::loadView('reportes.top-productos', ['items' => $items])->download('reporte-top-productos.pdf');
    }
}
