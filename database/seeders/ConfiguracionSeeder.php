<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Configuracion;

class ConfiguracionSeeder extends Seeder
{
    public function run(): void
    {
        $params = [
            ['clave' => 'tasa_interes_credito', 'valor' => '5.00',  'descripcion' => 'Interés por defecto al financiar una venta a crédito (%)'],
            ['clave' => 'tasa_mora_diaria',     'valor' => '0.50',  'descripcion' => 'Mora por día de retraso sobre la cuota vencida (%)'],
            ['clave' => 'tope_mora_pct',        'valor' => '20',    'descripcion' => 'Tope máximo de mora como % de la cuota'],
            ['clave' => 'dias_entre_cuotas',    'valor' => '30',    'descripcion' => 'Días entre vencimientos de cuotas consecutivas'],
            ['clave' => 'dias_aviso_cuota',     'valor' => '3',     'descripcion' => 'Días antes del vencimiento para avisar al cliente por email'],
        ];

        foreach ($params as $p) {
            Configuracion::updateOrCreate(['clave' => $p['clave']], $p);
        }
    }
}
