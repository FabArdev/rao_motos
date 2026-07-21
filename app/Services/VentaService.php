<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  VentaService — Creación de ventas (una sola tubería)
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Es la "caja registradora". Toma los productos de una venta
 *  (ya sea de mostrador o nacida de un pedido web), calcula el
 *  total sumando línea por línea, guarda la venta con su número
 *  (V-000001) y descuenta el stock. Siempre pasa por aquí, sin
 *  importar de dónde venga la venta, para no repetir lógica.
 *
 *  IMPLEMENTACIÓN
 *  - Tipo: Service (App\Services).
 *  - Depende de InventarioService (inyectado en el constructor).
 *  - Modelos: Venta, DetalleVenta, Producto.
 *  - crear(array $data): todo dentro de DB::transaction (o se
 *    guarda todo o nada). El total lo calcula el servidor, nunca
 *    se teclea (RN11). El precio de cada línea sale del umbral
 *    mayorista del producto con precioPara() (RN3).
 *  - descontar_stock (por defecto true): el stock se resta una
 *    sola vez; si el pedido ya lo descontó, se pasa en false (RN18).
 *  - Si una línea no tiene producto_id, es un servicio/mano de obra
 *    con precio dado.
 *  - Reglas de negocio: RN3, RN11, RN18, RN24.
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Services;

use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

class VentaService
{
    public function __construct(private InventarioService $inventario) {}

    public function crear(array $data): Venta
    {
        $descontarStock = $data['descontar_stock'] ?? true;

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

                    $precio = $producto->precioPara($cantidad);
                    $descripcion = null;
                    $productoId = $producto->id;
                } else {

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

                if ($descontarStock && $l['productoId']) {
                    $this->inventario->egreso($l['productoId'], $l['cantidad'], "Venta {$venta->numero_venta}");
                }
            }

            return $venta;
        });
    }
}
