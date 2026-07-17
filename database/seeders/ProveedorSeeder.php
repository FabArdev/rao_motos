<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use Illuminate\Database\Seeder;

class ProveedorSeeder extends Seeder
{
    public function run(): void
    {
        $proveedores = [
            ['razon_social' => 'Distribuidora Japonesa Ltda.', 'contacto_principal' => 'Tanaka Suzuki', 'nit' => '10012345', 'telefono' => '44123401'],
            ['razon_social' => 'Importadora China del Sur',    'contacto_principal' => 'Li Wei',        'nit' => '10067890', 'telefono' => '44123402'],
        ];

        foreach ($proveedores as $p) {
            Proveedor::create($p + ['activo' => true]);
        }
    }
}
