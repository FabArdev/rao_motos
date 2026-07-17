<?php

namespace Database\Seeders;

use App\Models\Inventario;
use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        // [codigo, nombre, marca, modelo, descripcion, precio_base, stock]
        $productos = [
            ['CAD-001', 'Kit Transmision Completo',    'DID',         '520VX3',  'Kit cadena 520 + pinon + corona 150cc',     350.00, 14],
            ['CAD-002', 'Cadena de Distribucion',      'Tsubaki',     'DID830',  'Cadena distribucion para motos 200cc',      180.00, 11],
            ['CAD-003', 'Pinon de Acero 15T',          'Sunstar',     '15T-520', 'Pinon delantero 15 dientes cadena 520',      85.00, 21],
            ['FRE-001', 'Pastillas Freno Delantero',   'EBC',         'FA209',   'Pastillas sinterizadas para freno disco',   120.00,  8],
            ['FRE-002', 'Disco Freno Trasero',         'Brembo',      'DB203',   'Disco freno trasero 220mm',                 250.00,  4],
            ['FRE-003', 'Cable de Freno Acero',        'Venhill',     'CB-15',   'Cable freno delantero con funda acero',      65.00, 24],
            ['BUJ-001', 'Bujia Iridium',               'NGK',         'CR8EIX',  'Bujia iridio para motos 125-250cc',          55.00,  8],
            ['BUJ-002', 'Bobina de Encendido',         'Denso',       '129700',  'Bobina encendido universal 12V',            180.00, 24],
            ['BUJ-003', 'CDI Electronico',             'Mitsubishi',  'CDI-150', 'Modulo encendido CDI para motos 150cc',     220.00,  4],
            ['FIL-001', 'Filtro de Aceite',            'Hiflofiltro', 'HF-204',  'Filtro aceite para motos 125-250cc',         35.00, 94],
            ['FIL-002', 'Filtro Aire Deportivo',       'K&N',         'KA-1508', 'Filtro aire alto flujo',                    160.00, 31],
            ['FIL-003', 'Filtro de Gasolina',          'Bosch',       '045-123', 'Filtro combustible universal',               25.00, 208],
            ['ACC-001', 'Espejo Retrovisor Universal', 'TST',         'MR-01',   'Espejo retrovisor negro universal',          70.00, 55],
            ['ACC-002', 'Manillar Deportivo',          'Renthal',     'RC-971',  'Manillar aluminio 28mm',                    200.00,  3],
            ['ACC-003', 'Cubre Carter Aluminio',       'Givi',        'GC-150',  'Cubre carter aluminio pulido',              310.00, 19],
        ];

        foreach ($productos as [$codigo, $nombre, $marca, $modelo, $descripcion, $base, $stock]) {
            $producto = Producto::create([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'marca' => $marca,
                'modelo' => $modelo,
                'descripcion' => $descripcion,
                'precio_venta_base' => $base,
                // Regla automática: mayorista ≈ 12% menos
                'precio_mayorista' => round($base * 0.88, 2),
                // Umbral por valor del repuesto: baratos umbral alto, caros umbral bajo
                'cantidad_minima_mayorista' => $this->umbral($base),
                'activo' => true,
            ]);

            Inventario::create([
                'producto_id' => $producto->id,
                'stock_actual' => $stock,
                'stock_minimo' => 5,
                'tecnica_inventario' => 'PERMANENTE',
                'tecnica_costo' => 'PROMEDIO',
                'fecha_actualizacion' => now(),
            ]);
        }
    }

    private function umbral(float $base): int
    {
        if ($base < 100) {
            return 20;
        }
        if ($base <= 200) {
            return 8;
        }

        return 3;
    }
}
