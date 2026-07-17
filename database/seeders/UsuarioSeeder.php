<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        // rol_id: 1=admin 2=vendedor 3=almacenero 5=cliente

        // --- 3 admins (equipo) ---
        $admins = [
            ['nombre' => 'Carlos Diego',    'apellidos' => 'Marca Peñaranda', 'correo' => 'marcacarlosestudio@gmail.com', 'ci' => '7000001', 'telefono' => '77123401'],
            ['nombre' => 'Fabio Alejandro', 'apellidos' => 'Arnez Fernández', 'correo' => 'fabioarnez200@gmail.com',      'ci' => '7000002', 'telefono' => '77123402'],
            ['nombre' => 'Reymar',          'apellidos' => 'Loaiza Labarden', 'correo' => 'loaizalabardenreymar@gmail.com', 'ci' => '7000003', 'telefono' => '77123403'],
        ];
        foreach ($admins as $a) {
            Usuario::create($a + ['password' => Hash::make('admin123'), 'rol_id' => 1, 'estado' => true]);
        }

        // --- 1 demo por rol operativo ---
        $staff = [
            ['nombre' => 'Vendedor',   'apellidos' => 'Demo', 'correo' => 'vendedor@raomotos.com',   'ci' => '8000001', 'telefono' => '70000001', 'rol_id' => 2],
            ['nombre' => 'Almacenero', 'apellidos' => 'Demo', 'correo' => 'almacenero@raomotos.com', 'ci' => '8000002', 'telefono' => '70000002', 'rol_id' => 3],
        ];
        foreach ($staff as $s) {
            Usuario::create($s + ['password' => Hash::make('demo123'), 'estado' => true]);
        }

        // --- 5 clientes (con su subtabla cliente) ---
        $clientes = [
            ['nombre' => 'Juan',  'apellidos' => 'Perez Mamani',     'correo' => 'juan.perez@email.com',      'ci' => '9000001', 'telefono' => '72123401', 'nit_ci' => '12345601'],
            ['nombre' => 'Maria', 'apellidos' => 'Flores Quispe',    'correo' => 'maria.flores@email.com',    'ci' => '9000002', 'telefono' => '72123402', 'nit_ci' => '12345602'],
            ['nombre' => 'Pedro', 'apellidos' => 'Gutierrez Soliz',  'correo' => 'pedro.gutierrez@email.com', 'ci' => '9000003', 'telefono' => '72123403', 'nit_ci' => '12345603'],
            ['nombre' => 'Ana',   'apellidos' => 'Rodriguez Lopez',  'correo' => 'ana.rodriguez@email.com',   'ci' => '9000004', 'telefono' => '72123404', 'nit_ci' => '12345604'],
            ['nombre' => 'Luis',  'apellidos' => 'Vargas Rojas',     'correo' => 'luis.vargas@email.com',     'ci' => '9000005', 'telefono' => '72123405', 'nit_ci' => '12345605'],
        ];
        foreach ($clientes as $c) {
            $usuario = Usuario::create([
                'nombre' => $c['nombre'], 'apellidos' => $c['apellidos'], 'correo' => $c['correo'],
                'ci' => $c['ci'], 'telefono' => $c['telefono'],
                'password' => Hash::make('cliente123'), 'rol_id' => 5, 'estado' => true,
            ]);
            Cliente::create(['id' => $usuario->id, 'nit_ci' => $c['nit_ci']]);
        }
    }
}
