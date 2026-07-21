<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  CreditoService — Créditos y calendario de cuotas
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Cuando una venta es a crédito (en cuotas), este archivo arma
 *  el "plan de pagos": calcula cuánto se debe con el interés,
 *  parte el total en cuotas iguales y les pone fecha de
 *  vencimiento. También sabe cobrar una cuota y cerrar el crédito
 *  cuando ya no queda nada por pagar. La multa por atraso (mora)
 *  la calcula aquí, pero quien la aplica cada día es la tarea
 *  programada MarcarCuotasVencidas.
 *
 *  IMPLEMENTACIÓN
 *  - Tipo: Service (App\Services).
 *  - Modelos: Credito, PagoCuota, Venta, Configuracion.
 *  - Usa Carbon para las fechas de vencimiento.
 *  - generar(): crea el crédito y sus cuotas; el saldo = total +
 *    total * tasaInteres/100; el redondeo sobrante va en la última
 *    cuota. Parámetros configurables: tasa_interes_credito,
 *    dias_entre_cuotas.
 *  - calcularMora(): diaria proporcional con tope (tasa_mora_diaria,
 *    tope_mora_pct); no guarda, sólo devuelve el monto.
 *  - registrarPagoCuota(): marca la cuota PAGADO, descuenta del
 *    saldo y pasa el crédito a PAGADO si ya no hay pendientes.
 *  - Reglas de negocio: RN8, RN12, RN16 (ALCANCE §CU7).
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Services;

use App\Models\Configuracion;
use App\Models\Credito;
use App\Models\PagoCuota;
use App\Models\Venta;
use Carbon\Carbon;

class CreditoService
{

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
