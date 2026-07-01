<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Cliente;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        // role_id: 1=admin 2=vendedor 3=almacenero 4=mecanico 5=cliente

        // --- 3 admins (equipo) ---
        $admins = [
            ['nombre' => 'Carlos Diego',    'apellidos' => 'Marca Peñaranda', 'email' => 'marcacarlosestudio@gmail.com', 'ci' => '7000001', 'telefono' => '77123401'],
            ['nombre' => 'Fabio Alejandro', 'apellidos' => 'Arnez Fernández', 'email' => 'fabioarnez200@gmail.com',      'ci' => '7000002', 'telefono' => '77123402'],
            ['nombre' => 'Reymar',          'apellidos' => 'Loaiza Labarden', 'email' => 'loaizalabardenreymar@gmail.com','ci' => '7000003', 'telefono' => '77123403'],
        ];
        foreach ($admins as $a) {
            User::create($a + ['password' => Hash::make('admin123'), 'role_id' => 1, 'estado' => true]);
        }

        // --- 1 demo por rol operativo ---
        $staff = [
            ['nombre' => 'Vendedor',   'apellidos' => 'Demo', 'email' => 'vendedor@raomotos.com',   'ci' => '8000001', 'telefono' => '70000001', 'role_id' => 2],
            ['nombre' => 'Almacenero', 'apellidos' => 'Demo', 'email' => 'almacenero@raomotos.com', 'ci' => '8000002', 'telefono' => '70000002', 'role_id' => 3],
            ['nombre' => 'Mecanico',   'apellidos' => 'Demo', 'email' => 'mecanico@raomotos.com',   'ci' => '8000003', 'telefono' => '70000003', 'role_id' => 4],
        ];
        foreach ($staff as $s) {
            User::create($s + ['password' => Hash::make('demo123'), 'estado' => true]);
        }

        // --- 5 clientes (con su subtabla cliente) ---
        $clientes = [
            ['nombre' => 'Juan',  'apellidos' => 'Perez Mamani',     'email' => 'juan.perez@email.com',      'ci' => '9000001', 'telefono' => '72123401', 'nit_ci' => '12345601'],
            ['nombre' => 'Maria', 'apellidos' => 'Flores Quispe',    'email' => 'maria.flores@email.com',    'ci' => '9000002', 'telefono' => '72123402', 'nit_ci' => '12345602'],
            ['nombre' => 'Pedro', 'apellidos' => 'Gutierrez Soliz',  'email' => 'pedro.gutierrez@email.com', 'ci' => '9000003', 'telefono' => '72123403', 'nit_ci' => '12345603'],
            ['nombre' => 'Ana',   'apellidos' => 'Rodriguez Lopez',  'email' => 'ana.rodriguez@email.com',   'ci' => '9000004', 'telefono' => '72123404', 'nit_ci' => '12345604'],
            ['nombre' => 'Luis',  'apellidos' => 'Vargas Rojas',     'email' => 'luis.vargas@email.com',     'ci' => '9000005', 'telefono' => '72123405', 'nit_ci' => '12345605'],
        ];
        foreach ($clientes as $c) {
            $user = User::create([
                'nombre' => $c['nombre'], 'apellidos' => $c['apellidos'], 'email' => $c['email'],
                'ci' => $c['ci'], 'telefono' => $c['telefono'],
                'password' => Hash::make('cliente123'), 'role_id' => 5, 'estado' => true,
            ]);
            Cliente::create(['id' => $user->id, 'nit_ci' => $c['nit_ci']]);
        }
    }
}
