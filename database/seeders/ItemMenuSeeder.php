<?php

namespace Database\Seeders;

use App\Models\ItemMenu;
use Illuminate\Database\Seeder;

class ItemMenuSeeder extends Seeder
{
    public function run(): void
    {
        // rol_id => [ [etiqueta, ruta_laravel, icono], ... ]
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

        foreach ($menus as $rolId => $items) {
            $orden = 1;
            foreach ($items as [$etiqueta, $ruta, $icono]) {
                ItemMenu::create([
                    'etiqueta' => $etiqueta,
                    'ruta_laravel' => $ruta,
                    'icono' => $icono,
                    'orden' => $orden++,
                    'rol_id' => $rolId,
                    'activo' => true,
                ]);
            }
        }
    }
}
