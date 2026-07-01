<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ConfiguracionSeeder::class,
            MetodoPagoSeeder::class,
            UsuarioSeeder::class,
            ProveedorSeeder::class,
            ProductoSeeder::class,
            MenuItemSeeder::class,
        ]);
    }
}
