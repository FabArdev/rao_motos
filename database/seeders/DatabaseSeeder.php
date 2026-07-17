<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            ConfiguracionSeeder::class,
            MetodoPagoSeeder::class,
            UsuarioSeeder::class,
            ProveedorSeeder::class,
            ProductoSeeder::class,
            ItemMenuSeeder::class,
        ]);
    }
}
