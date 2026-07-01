<?php

namespace App\Services;

use App\Models\Configuracion;
use App\Models\Credito;
use App\Models\PagoCuota;
use App\Models\Venta;
use Carbon\Carbon;

/**
 * Genera y mantiene créditos y su calendario de cuotas.
 * El interés infla el saldo al inicio; la mora es aparte (por atraso), la calcula
 * la tarea programada diaria (MarcarCuotasVencidas). Ver ALCANCE §CU7 / RN8 / RN16.
 */
class CreditoService
{
    /**
     * Crea un crédito para una venta y genera su calendario de cuotas.
     *
     * @param  float  $tasaInteres  interés % por crédito (default configurable).
     */
    public function generar(Venta $venta, int $numeroCuotas, ?float $tasaInteres = null): Credito
    {
        $tasaInteres ??= (float) Configuracion::valor('tasa_interes_credito', 5.00);
        $diasEntreCuotas = (int) Configuracion::valor('dias_entre_cuotas', 30);

        $montoTotal = (float) $venta->monto_total;
        $saldo = round($montoTotal + $montoTotal * $tasaInteres / 100, 2);

        $credito = Credito::create([
            'venta_id' => $venta->id,
            'numero_cuotas' => $numeroCuotas,
            'tasa_interes' => $tasaInteres,
            'saldo_pendiente' => $saldo,
            'estado' => 'VIGENTE',
        ]);

        // Reparto en cuotas iguales; el redondeo residual va en la última cuota.
        $base = floor($saldo / $numeroCuotas * 100) / 100;
        $acumulado = 0;

        for ($i = 1; $i <= $numeroCuotas; $i++) {
            $monto = $i === $numeroCuotas ? round($saldo - $acumulado, 2) : $base;
            $acumulado += $monto;

            PagoCuota::create([
                'credito_id' => $credito->id,
                'numero_cuota' => $i,
                'monto_cuota' => $monto,
                'fecha_vencimiento' => Carbon::now()->addDays($diasEntreCuotas * $i)->toDateString(),
                'mora' => 0,
                'estado' => 'PENDIENTE',
            ]);
        }

        return $credito;
    }

    /**
     * Calcula la mora de una cuota vencida: diaria proporcional con tope (RN16).
     * No persiste; devuelve el monto de mora para la fecha dada.
     */
    public function calcularMora(PagoCuota $cuota, ?Carbon $hasta = null): float
    {
        $hasta ??= Carbon::now();
        $vencimiento = Carbon::parse($cuota->fecha_vencimiento);

        if ($hasta->lte($vencimiento)) {
            return 0.0;
        }

        $diasRetraso = $vencimiento->diffInDays($hasta);
        $tasaMoraDiaria = (float) Configuracion::valor('tasa_mora_diaria', 0.50);
        $topePct = (float) Configuracion::valor('tope_mora_pct', 20);

        $mora = (float) $cuota->monto_cuota * ($tasaMoraDiaria / 100) * $diasRetraso;
        $tope = (float) $cuota->monto_cuota * ($topePct / 100);

        return round(min($mora, $tope), 2);
    }

    /**
     * Registra el pago de una cuota (efectivo o QR ya confirmado).
     * Descuenta del saldo, marca la cuota PAGADO y cierra el crédito si ya no quedan pendientes.
     */
    public function registrarPagoCuota(PagoCuota $cuota, ?int $metodoPagoId = null): void
    {
        $mora = $this->calcularMora($cuota);

        $cuota->update([
            'estado' => 'PAGADO',
            'fecha_pago' => Carbon::now()->toDateString(),
            'mora' => $mora,
            'metodo_pago_id' => $metodoPagoId,
        ]);

        $credito = $cuota->credito;
        $nuevoSaldo = max(0, round((float) $credito->saldo_pendiente - (float) $cuota->monto_cuota, 2));

        $pendientes = $credito->cuotas()->where('estado', '!=', 'PAGADO')->count();

        $credito->update([
            'saldo_pendiente' => $nuevoSaldo,
            'estado' => $pendientes === 0 ? 'PAGADO' : 'VIGENTE',
        ]);
    }
}
