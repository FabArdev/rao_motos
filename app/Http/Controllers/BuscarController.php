<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\OrdenTrabajo;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Búsqueda global del negocio (REQ9): productos, clientes, pedidos y órdenes de taller.
 * Los resultados se acotan según el rol para no exponer datos de otros.
 */
class BuscarController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->string('q')->toString());
        $user = $request->user();
        $esCliente = $user->tieneRol('cliente');

        $resultados = ['productos' => [], 'clientes' => [], 'pedidos' => [], 'ordenes' => []];

        if (strlen($q) >= 2) {
            $like = "%{$q}%";

            // Productos: para todos (el cliente los ve como catálogo).
            $resultados['productos'] = Producto::where('activo', true)
                ->where(fn ($s) => $s->where('nombre', 'ilike', $like)->orWhere('codigo', 'ilike', $like)
                    ->orWhere('marca', 'ilike', $like)->orWhere('modelo', 'ilike', $like))
                ->limit(10)->get(['id', 'codigo', 'nombre', 'marca']);

            if (! $esCliente) {
                // Clientes, pedidos y órdenes: solo staff.
                $resultados['clientes'] = Cliente::with('user:id,nombre,apellidos,ci')
                    ->whereHas('user', fn ($u) => $u->where('nombre', 'ilike', $like)
                        ->orWhere('apellidos', 'ilike', $like)->orWhere('ci', 'ilike', $like))
                    ->limit(10)->get()
                    ->map(fn ($c) => ['id' => $c->id, 'nombre' => $c->user?->name, 'ci' => $c->user?->ci]);

                $resultados['pedidos'] = Pedido::with('cliente.user:id,nombre,apellidos')
                    ->when(is_numeric($q), fn ($query) => $query->where('id', $q))
                    ->limit(10)->get()
                    ->map(fn ($p) => ['id' => $p->id, 'cliente' => $p->cliente?->user?->name, 'estado' => $p->estado]);

                $resultados['ordenes'] = OrdenTrabajo::with(['cliente.user:id,nombre,apellidos', 'moto:id,placa'])
                    ->when(is_numeric($q), fn ($query) => $query->where('id', $q))
                    ->orWhereHas('moto', fn ($m) => $m->where('placa', 'ilike', $like))
                    ->limit(10)->get()
                    ->map(fn ($o) => ['id' => $o->id, 'cliente' => $o->cliente?->user?->name, 'placa' => $o->moto?->placa, 'estado' => $o->estado]);
            }
        }

        return Inertia::render('Buscar/Index', [
            'q' => $q,
            'resultados' => $resultados,
            'esCliente' => $esCliente,
        ]);
    }
}
