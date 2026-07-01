<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // ids fijos para referencias estables (1=admin ... 5=cliente)
        DB::table('roles')->insert([
            ['id' => 1, 'nombre' => 'admin',      'descripcion' => 'Administrador / propietario, acceso total', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'nombre' => 'vendedor',   'descripcion' => 'Ventas, pedidos, cobranza, factura taller', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'nombre' => 'almacenero', 'descripcion' => 'Compras, proveedores, inventario, productos', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'nombre' => 'mecanico',   'descripcion' => 'Órdenes de taller, diagnóstico, reparación', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'nombre' => 'cliente',    'descripcion' => 'Compra, pedidos, sus cuotas y su moto',       'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
