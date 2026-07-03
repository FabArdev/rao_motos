<?php

namespace App\Http\Controllers;

use App\Models\Credito;
use App\Models\Pedido;
use App\Models\Venta;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Refuerzo C — panel unificado del cliente: sus compras (contado/crédito),
 * sus pedidos y sus cuotas en una sola vista.
 */
class MiCuentaController extends Controller
{
    public function index(Request $request)
    {
        $clienteId = $request->user()->id;

        return Inertia::render('MiCuenta/Index', [
            'compras' => Venta::with(['detalles:id,venta_id,producto_id,descripcion,cantidad', 'detalles.producto:id,nombre'])
                ->where('cliente_id', $clienteId)
                ->latest('fecha')->limit(8)
                ->get(['id', 'numero_venta', 'fecha', 'tipo_venta', 'monto_total', 'estado']),
            'pedidos' => Pedido::where('cliente_id', $clienteId)->latest('fecha')->limit(5)->get(['id', 'fecha', 'estado']),
            'creditos' => Credito::with('venta:id,numero_venta,cliente_id')
                ->whereHas('venta', fn ($q) => $q->where('cliente_id', $clienteId))
                ->get(['id', 'venta_id', 'estado', 'saldo_pendiente']),
        ]);
    }
}
