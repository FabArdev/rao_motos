<?php

namespace App\Services;

use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Notificacion;
use App\Models\User;

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
     * Egreso de stock (venta directa, venta desde pedido).
     * Valida que haya stock suficiente y dispara la alerta de stock bajo.
     */
    public function egreso(int $productoId, int $cantidad, string $motivo): void
    {
        $inv = $this->inventarioDe($productoId);

        if ($inv->stock_actual < $cantidad) {
            throw new \RuntimeException(
                "Stock insuficiente para el producto #{$productoId}: disponible {$inv->stock_actual}, requerido {$cantidad}."
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

        $almaceneros = User::whereHas('role', fn ($q) => $q->where('nombre', 'almacenero'))->pluck('id');

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
