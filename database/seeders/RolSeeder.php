<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        // ids fijos para referencias estables (1=admin ... 5=cliente)
        DB::table('rol')->insert([
            ['id' => 1, 'nombre' => 'admin',      'descripcion' => 'Administrador / propietario, acceso total', 'creado_en' => now(), 'actualizado_en' => now()],
            ['id' => 2, 'nombre' => 'vendedor',   'descripcion' => 'Ventas, pedidos y cobranza',                 'creado_en' => now(), 'actualizado_en' => now()],
            ['id' => 3, 'nombre' => 'almacenero', 'descripcion' => 'Compras, proveedores, inventario, productos', 'creado_en' => now(), 'actualizado_en' => now()],
            ['id' => 5, 'nombre' => 'cliente',    'descripcion' => 'Compra, pedidos y sus cuotas',               'creado_en' => now(), 'actualizado_en' => now()],
        ]);
    }
}
