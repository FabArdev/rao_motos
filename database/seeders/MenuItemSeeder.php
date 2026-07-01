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
                ['Taller', 'taller.index', 'tools'],
                ['Reportes', 'reportes.index', 'bar-chart'],
                ['Bitácora', 'bitacora.index', 'journal-text'],
                ['Configuración', 'configuracion.index', 'gear'],
            ],
            2 => [ // vendedor
                ['Dashboard', 'dashboard', 'speedometer2'],
                ['Pedidos', 'pedidos.index', 'bag'],
                ['Ventas', 'ventas.index', 'receipt'],
                ['Créditos', 'creditos.index', 'credit-card'],
                ['Taller', 'taller.index', 'tools'],
                ['Reportes', 'reportes.index', 'bar-chart'],
            ],
            3 => [ // almacenero
                ['Dashboard', 'dashboard', 'speedometer2'],
                ['Productos', 'productos.index', 'box-seam'],
                ['Inventario', 'inventario.index', 'clipboard-data'],
                ['Compras', 'compras.index', 'cart-plus'],
                ['Proveedores', 'proveedores.index', 'truck'],
            ],
            4 => [ // mecanico
                ['Dashboard', 'dashboard', 'speedometer2'],
                ['Taller', 'taller.index', 'tools'],
            ],
            5 => [ // cliente
                ['Dashboard', 'dashboard', 'speedometer2'],
                ['Catálogo', 'catalogo.index', 'shop'],
                ['Mis Pedidos', 'mis-pedidos.index', 'bag'],
                ['Mi Taller', 'mi-taller.index', 'tools'],
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
