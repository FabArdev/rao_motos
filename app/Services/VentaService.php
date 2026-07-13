<?php

namespace App\Services;

use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

/**
 * Crea ventas (mostrador o desde pedido) con el mismo pipeline.
 * El total lo calcula el servidor por línea (RN11); el precio por línea sale del
 * umbral mayorista del producto (RN3). El stock se descuenta aquí una sola vez,
 * salvo que el origen ya lo haya descontado (pedido → descontarStock=false, RN18).
 */
class VentaService
{
    public function __construct(private InventarioService $inventario) {}

    /**
     * @param  array  $data  {
     *                       cliente_id, vendedor_id, tipo_venta[CONTADO|CREDITO], metodo_pago[EFECTIVO|QR],
     *                       estado?, descontar_stock?(bool, def true),
     *                       items: [ { producto_id?, descripcion?, cantidad, precio_unitario? } ]
     *                       }
     */
    public function crear(array $data): Venta
    {
        $descontarStock = $data['descontar_stock'] ?? true;

        // Si esta venta saca stock ahora, verifica disponibilidad antes de crear nada (mensaje claro).
        if ($descontarStock) {
            $this->inventario->verificarStock($data['items']);
        }

        return DB::transaction(function () use ($data, $descontarStock) {
            $lineas = [];
            $total = 0;

            foreach ($data['items'] as $item) {
                $cantidad = (int) $item['cantidad'];

                if (! empty($item['producto_id'])) {
                    $producto = Producto::findOrFail($item['producto_id']);
                    // Precio por línea según el umbral del producto (RN3), nunca tecleado.
                    $precio = $producto->precioPara($cantidad);
                    $descripcion = null;
                    $productoId = $producto->id;
                } else {
                    // Línea de servicio / mano de obra: el precio viene dado.
                    $precio = (float) $item['precio_unitario'];
                    $descripcion = $item['descripcion'] ?? 'Servicio';
                    $productoId = null;
                }

                $total += $precio * $cantidad;
                $lineas[] = compact('productoId', 'descripcion', 'cantidad', 'precio');
            }

            $venta = Venta::create([
                'numero_venta' => null,
                'cliente_id' => $data['cliente_id'],
                'vendedor_id' => $data['vendedor_id'] ?? null,
                'fecha' => now(),
                'monto_total' => round($total, 2),
                'tipo_venta' => $data['tipo_venta'],
                'metodo_pago' => $data['metodo_pago'],
                'estado' => $data['estado'] ?? 'PENDIENTE',
            ]);

            $venta->update(['numero_venta' => 'V-'.str_pad($venta->id, 6, '0', STR_PAD_LEFT)]);

            foreach ($lineas as $l) {
                DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $l['productoId'],
                    'descripcion' => $l['descripcion'],
                    'cantidad' => $l['cantidad'],
                    'precio_unitario' => $l['precio'],
                ]);

                // El stock sale físicamente aquí, salvo que ya se haya descontado en el origen.
                if ($descontarStock && $l['productoId']) {
                    $this->inventario->egreso($l['productoId'], $l['cantidad'], "Venta {$venta->numero_venta}");
                }
            }

            return $venta;
        });
    }
}
