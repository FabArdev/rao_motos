<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\MenuItem;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Búsqueda global del negocio (REQ9): funcionalidades, productos, clientes y pedidos.
 * Los resultados se acotan según el rol para no exponer datos ni módulos ajenos:
 * las funcionalidades salen del menú del propio rol (un cliente nunca ve "Reportes").
 */
class BuscarController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->string('q')->toString());
        $user = $request->user();
        $esCliente = $user->tieneRol('cliente');

        $resultados = ['funcionalidades' => [], 'productos' => [], 'clientes' => [], 'pedidos' => []];

        if (strlen($q) >= 2) {
            $like = "%{$q}%";

            // Funcionalidades/módulos que el rol del usuario puede abrir (menú del rol).
            // Es role-safe: solo se listan las páginas a las que ese rol tiene acceso.
            $resultados['funcionalidades'] = MenuItem::where('role_id', $user->role_id)
                ->where('activo', true)
                ->where('etiqueta', 'ilike', $like)
                ->orderBy('orden')
                ->get(['etiqueta', 'ruta_laravel', 'icono'])
                ->map(fn ($m) => ['etiqueta' => $m->etiqueta, 'ruta' => $m->ruta_laravel, 'icono' => $m->icono])
                ->all();

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
            }
        }

        return Inertia::render('Buscar/Index', [
            'q' => $q,
            'resultados' => $resultados,
            'esCliente' => $esCliente,
        ]);
    }
}
