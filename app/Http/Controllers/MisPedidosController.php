<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MisPedidosController extends Controller
{
    public function index(Request $request)
    {
        $pedidos = Pedido::with('venta')
            ->withCount('detalles')
            ->where('cliente_id', $request->user()->id)
            ->latest('fecha')
            ->paginate(12);

        return Inertia::render('MisPedidos/Index', ['pedidos' => $pedidos]);
    }

    public function show(Request $request, Pedido $pedido)
    {
        // Un cliente solo ve sus propios pedidos.
        if ($pedido->cliente_id !== $request->user()->id) {
            throw new NotFoundHttpException;
        }

        $pedido->load(['detalles.producto', 'venta']);

        return Inertia::render('MisPedidos/Show', ['pedido' => $pedido]);
    }
}
