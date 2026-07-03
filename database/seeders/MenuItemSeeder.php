<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuItem;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        // role_id => [ [etiqueta, ruta_laravel, icono], ... ]
        $menus = [
            1 => [ // admin
                ['Dashboard', 'dashboard', 'speedometer2'],
                ['Usuarios', 'usuarios.index', 'people'],
                ['Productos', 'productos.index', 'box-seam'],
                ['Inventario', 'inventario.index', 'clipboard-data'],
                ['Compras', 'compras.index', 'cart-plus'],
                ['Proveedores', 'proveedores.index', 'truck'],
                ['Pedidos', 'pedidos.index', 'bag'],
                ['Ventas', 'ventas.index', 'receipt'],
                ['Créditos', 'creditos.index', 'credit-card'],
                ['Reportes', 'reportes.index', 'bar-chart'],
                ['Bitácora', 'bitacora.index', 'journal-text'],
                ['Configuración', 'configuracion.index', 'gear'],
            ],
            2 => [ // vendedor
                ['Pedidos', 'pedidos.index', 'bag'],
                ['Ventas', 'ventas.index', 'receipt'],
                ['Créditos', 'creditos.index', 'credit-card'],
                ['Reportes', 'reportes.index', 'bar-chart'],
            ],
            3 => [ // almacenero
                ['Productos', 'productos.index', 'box-seam'],
                ['Inventario', 'inventario.index', 'clipboard-data'],
                ['Compras', 'compras.index', 'cart-plus'],
                ['Proveedores', 'proveedores.index', 'truck'],
                ['Despachos', 'despachos.index', 'box-arrow-right'],
            ],
            5 => [ // cliente
                ['Catálogo', 'catalogo.index', 'shop'],
                ['Mis Pedidos', 'mis-pedidos.index', 'bag'],
                ['Mis Créditos', 'mis-creditos.index', 'credit-card'],
                ['Mi Cuenta', 'mi-cuenta.index', 'person-circle'],
            ],
        ];

        foreach ($menus as $roleId => $items) {
            $orden = 1;
            foreach ($items as [$etiqueta, $ruta, $icono]) {
                MenuItem::create([
                    'etiqueta' => $etiqueta,
                    'ruta_laravel' => $ruta,
                    'icono' => $icono,
                    'orden' => $orden++,
                    'role_id' => $roleId,
                    'activo' => true,
                ]);
            }
        }
    }
}
