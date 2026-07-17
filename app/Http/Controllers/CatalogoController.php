<?php

namespace App\Http\Controllers;

use App\Models\DetallePedido;
use App\Models\Notificacion;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CatalogoController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->string('q')->toString();

        $productos = Producto::with(['inventario:id,producto_id,stock_actual', 'imagenes:id,producto_id,ruta,orden'])
            ->where('activo', true)
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('nombre', 'ilike', "%{$q}%")
                        ->orWhere('codigo', 'ilike', "%{$q}%")
                        ->orWhere('marca', 'ilike', "%{$q}%")
                        ->orWhere('modelo', 'ilike', "%{$q}%");
                });
            })
            ->orderBy('nombre')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Catalogo/Index', [
            'productos' => $productos,
            'filtros' => ['q' => $q],
        ]);
    }

    /** El cliente arma su pedido (estado SOLICITADO) y notifica a los vendedores. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'exists:producto,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ], [
            'items.required' => 'Agregue al menos un producto al pedido.',
            'items.min' => 'Agregue al menos un producto al pedido.',
        ]);

        $pedido = DB::transaction(function () use ($data, $request) {
            $pedido = Pedido::create([
                'cliente_id' => $request->user()->id,
                'fecha' => now(),
                'estado' => 'SOLICITADO',
            ]);

            foreach ($data['items'] as $it) {
                DetallePedido::create([
                    'pedido_id' => $pedido->id,
                    'producto_id' => $it['producto_id'],
                    'cantidad' => $it['cantidad'],
                ]);
            }

            // Aviso in-app a los vendedores (pedido por aprobar).
            $vendedores = Usuario::whereHas('rol', fn ($r) => $r->where('nombre', 'vendedor'))->pluck('id');
            foreach ($vendedores as $usuarioId) {
                Notificacion::create([
                    'usuario_id' => $usuarioId,
                    'tipo' => 'PEDIDO_POR_APROBAR',
                    'mensaje' => "Nuevo pedido #{$pedido->id} de {$request->user()->nombre_completo} por aprobar.",
                    'recurso' => route('pedidos.show', $pedido->id),
                    'leido' => false,
                    'fecha' => now(),
                ]);
            }

            return $pedido;
        });

        return redirect()->route('mis-pedidos.show', $pedido->id)->with('success', 'Pedido enviado. Un vendedor lo revisará.');
    }
}
