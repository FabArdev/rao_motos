<?php

namespace Database\Seeders;

use App\Models\MetodoPago;
use Illuminate\Database\Seeder;

class MetodoPagoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['EFECTIVO', 'QR'] as $nombre) {
            MetodoPago::updateOrCreate(['nombre' => $nombre], ['activo' => true]);
        }
    }
}
