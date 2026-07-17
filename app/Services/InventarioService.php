<?php

namespace App\Services;

use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Notificacion;
use App\Models\Producto;
use App\Models\Usuario;

/**
 * Centraliza el movimiento de stock. El stock se toca aquí y solo aquí,
 * de modo que compras (ingreso) y ventas (egreso) queden consistentes
 * y cada movimiento quede registrado en movimiento_inventario (RN18).
 */
class InventarioService
{
    /** Ingreso de stock (recepción de compra, ajuste positivo). */
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

    /**
     * Verifica que haya stock suficiente para un conjunto de líneas {producto_id, cantidad}
     * ANTES de comprometer la operación (venta de mostrador, aprobación de pedido). Suma las
     * cantidades del mismo producto y lanza RuntimeException con NOMBRES si algo no alcanza.
     */
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

    /**
     * Egreso de stock (venta directa, venta desde pedido).
     * Valida que haya stock suficiente y dispara la alerta de stock bajo.
     */
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

    /** Notifica a los almaceneros (in-app, refuerzo A) que un producto cayó bajo el mínimo. */
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
