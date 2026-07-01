<?php

namespace App\Console\Commands;

use App\Models\Credito;
use App\Models\Notificacion;
use App\Models\PagoCuota;
use App\Services\CreditoService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Tarea programada diaria (RN12): detecta cuotas vencidas, calcula su mora,
 * marca la cuota VENCIDO y pone el crédito en MOROSO. No depende de que
 * alguien intente pagar. Corrige el defecto L8 del proyecto anterior.
 */
class MarcarCuotasVencidas extends Command
{
    protected $signature = 'creditos:marcar-vencidas';

    protected $description = 'Marca cuotas vencidas, calcula la mora y pone los créditos en MOROSO';

    public function handle(CreditoService $creditos): int
    {
        $hoy = Carbon::today();

        $vencidas = PagoCuota::where('estado', 'PENDIENTE')
            ->whereDate('fecha_vencimiento', '<', $hoy)
            ->get();

        $creditosAfectados = [];

        DB::transaction(function () use ($vencidas, $creditos, &$creditosAfectados) {
            foreach ($vencidas as $cuota) {
                $mora = $creditos->calcularMora($cuota);
                $cuota->update(['estado' => 'VENCIDO', 'mora' => $mora]);
                $creditosAfectados[$cuota->credito_id] = true;
            }

            foreach (array_keys($creditosAfectados) as $creditoId) {
                $credito = Credito::with('venta.cliente')->find($creditoId);
                if (! $credito || $credito->estado === 'PAGADO') {
                    continue;
                }
                $credito->update(['estado' => 'MOROSO']);

                // Aviso in-app al cliente (el email de mora se envía en la fase SMTP).
                $clienteId = $credito->venta?->cliente_id;
                if ($clienteId) {
                    Notificacion::create([
                        'usuario_id' => $clienteId,
                        'tipo' => 'MORA',
                        'mensaje' => "Tu crédito de la venta {$credito->venta?->numero_venta} tiene cuotas vencidas.",
                        'recurso' => null,
                        'leido' => false,
                        'fecha' => now(),
                    ]);
                }
            }
        });

        $this->info("Cuotas vencidas procesadas: {$vencidas->count()}. Créditos en mora: ".count($creditosAfectados).'.');

        return self::SUCCESS;
    }
}
