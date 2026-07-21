<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  InventarioService — Movimiento de stock (almacén central)
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Es el "almacén" del sistema. Cada vez que entran productos
 *  (una compra) o salen (una venta), la cantidad en stock se
 *  cambia AQUÍ y sólo aquí, para que los números nunca se
 *  descuadren. Además, si un producto baja del mínimo, avisa
 *  al almacenero. Lo usan las ventas, las compras y los pedidos.
 *
 *  IMPLEMENTACIÓN
 *  - Tipo: Service (App\Services), capa de lógica de negocio.
 *  - Modelos que usa: Inventario, MovimientoInventario, Producto,
 *    Notificacion, Usuario.
 *  - Métodos: ingreso() suma stock; egreso() resta stock (valida
 *    que alcance); verificarStock() revisa varias líneas antes de
 *    vender; alertarStockBajo() crea la notificación STOCK_BAJO.
 *  - Cada movimiento queda registrado en movimiento_inventario.
 *  - lockForUpdate() bloquea la fila para evitar condiciones de
 *    carrera cuando dos operaciones tocan el mismo stock.
 *  - Lanza \RuntimeException con nombres de producto si falta
 *    stock. Reglas de negocio: RN18 y RN24.
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Services;

use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Notificacion;
use App\Models\Producto;
use App\Models\Usuario;

class InventarioService
{
    public function ingreso(int $productoId, int $cantidad, string $motivo): void
    {
        $inv = $this->inventarioDe($productoId);

        $inv->increment('stock_actual', $cantidad);
        $inv->update(['fecha_actualizacion' => now()]);

        MovimientoInventario::create([
            'inventario_id' => $inv->id,
            'tipo_movimiento' => 'INGRESO',
            'cantidad' => $cantidad,
            'motivo' => $motivo,
            'fecha' => now(),
        ]);
    }

    public function verificarStock(array $items): void
    {
        $requeridoPorProducto = [];
        foreach ($items as $item) {
            if (empty($item['producto_id'])) {
                continue;
            }
            $id = (int) $item['producto_id'];
            $requeridoPorProducto[$id] = ($requeridoPorProducto[$id] ?? 0) + (int) $item['cantidad'];
        }

        $faltantes = [];
        foreach ($requeridoPorProducto as $productoId => $requerido) {
            $disponible = (int) (Inventario::where('producto_id', $productoId)->value('stock_actual') ?? 0);
            if ($disponible < $requerido) {
                $nombre = Producto::whereKey($productoId)->value('nombre') ?? "producto #{$productoId}";
                $faltantes[] = "{$nombre} (disponible {$disponible}, requiere {$requerido})";
            }
        }

        if ($faltantes) {
            throw new \RuntimeException('No hay stock suficiente para: '.implode('; ', $faltantes).'.');
        }
    }

    public function egreso(int $productoId, int $cantidad, string $motivo): void
    {
        $inv = $this->inventarioDe($productoId);

        if ($inv->stock_actual < $cantidad) {
            $nombre = Producto::whereKey($productoId)->value('nombre') ?? "producto #{$productoId}";
            throw new \RuntimeException(
                "No hay stock suficiente para {$nombre}: disponible {$inv->stock_actual}, requiere {$cantidad}."
            );
        }

        $inv->decrement('stock_actual', $cantidad);
        $inv->update(['fecha_actualizacion' => now()]);

        MovimientoInventario::create([
            'inventario_id' => $inv->id,
            'tipo_movimiento' => 'EGRESO',
            'cantidad' => $cantidad,
            'motivo' => $motivo,
            'fecha' => now(),
        ]);

        $inv->refresh();
        if ($inv->bajoMinimo()) {
            $this->alertarStockBajo($inv);
        }
    }

    private function inventarioDe(int $productoId): Inventario
    {
        return Inventario::where('producto_id', $productoId)->lockForUpdate()->firstOrFail();
    }

    private function alertarStockBajo(Inventario $inv): void
    {
        $inv->loadMissing('producto');
        $nombre = $inv->producto?->nombre ?? "producto #{$inv->producto_id}";

        $almaceneros = Usuario::whereHas('rol', fn ($q) => $q->where('nombre', 'almacenero'))->pluck('id');

        foreach ($almaceneros as $usuarioId) {
            Notificacion::create([
                'usuario_id' => $usuarioId,
                'tipo' => 'STOCK_BAJO',
                'mensaje' => "Stock bajo: {$nombre} ({$inv->stock_actual}/{$inv->stock_minimo}).",
                'recurso' => route('inventario.show', $inv->id),
                'leido' => false,
                'fecha' => now(),
            ]);
        }
    }
}
