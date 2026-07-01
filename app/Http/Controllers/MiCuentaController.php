<?php

namespace App\Http\Controllers;

use App\Models\Credito;
use App\Models\Moto;
use App\Models\OrdenTrabajo;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Refuerzo C — panel unificado del cliente: sus pedidos, su moto en taller,
 * sus cuotas y sus motos en una sola vista.
 */
class MiCuentaController extends Controller
{
    public function index(Request $request)
    {
        $clienteId = $request->user()->id;

        return Inertia::render('MiCuenta/Index', [
            'pedidos' => Pedido::where('cliente_id', $clienteId)->latest('fecha')->limit(5)->get(['id', 'fecha', 'estado']),
            'ordenes' => OrdenTrabajo::with('moto:id,marca,modelo,placa')
                ->where('cliente_id', $clienteId)->latest('fecha_ingreso')->limit(5)->get(),
            'creditos' => Credito::with('venta:id,numero_venta,cliente_id')
                ->whereHas('venta', fn ($q) => $q->where('cliente_id', $clienteId))
                ->get(['id', 'venta_id', 'estado', 'saldo_pendiente']),
            'motos' => Moto::where('cliente_id', $clienteId)->get(['id', 'placa', 'marca', 'modelo', 'anio']),
        ]);
    }
}
