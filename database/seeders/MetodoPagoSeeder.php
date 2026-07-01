<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MetodoPago;

class MetodoPagoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['EFECTIVO', 'QR'] as $nombre) {
            MetodoPago::updateOrCreate(['nombre' => $nombre], ['activo' => true]);
        }
    }
}
