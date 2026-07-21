<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  TieneQrPagoFacil — QR de PagoFácil con vencimiento
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  El QR que devuelve PagoFácil vive muy poco (unos segundos /
 *  minutos): pasada esa hora el banco ya no lo acepta. Este
 *  comportamiento compartido responde dos cosas para la venta y
 *  para la cuota por igual: "¿el QR guardado todavía sirve?" y
 *  "guarda este QR nuevo y olvida el anterior".
 *
 *  IMPLEMENTACIÓN
 *  - Tipo: trait de modelo (App\Models\Concerns). Lo usan Venta y
 *    PagoCuota; ambas tienen las mismas columnas pago_facil_*.
 *  - qrPagoFacilVigente(): exige transacción + imagen + estado
 *    'pending' + fecha de expiración futura. Sin fecha (QR viejo,
 *    anterior a la columna) se considera vencido para forzar uno nuevo.
 *  - guardarQrPagoFacil(): sobreescribe TODAS las columnas del QR,
 *    así el QR anterior queda descartado y nunca se muestra otra vez.
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Models\Concerns;

use Carbon\Carbon;

trait TieneQrPagoFacil
{
    /** ¿El QR guardado sigue sirviendo para cobrar? */
    public function qrPagoFacilVigente(): bool
    {
        if (! $this->pago_facil_id_transaccion || ! $this->pago_facil_imagen_qr) {
            return false;
        }

        if ($this->pago_facil_estado !== 'pending') {
            return false;
        }

        if (! $this->pago_facil_expira_en) {
            return false;
        }

        return Carbon::parse($this->pago_facil_expira_en)->isFuture();
    }

    /**
     * Guarda el QR recién generado, descartando por completo el anterior.
     *
     * @param  array  $qr  Respuesta de PagoFacilService::generarQR*
     * @param  array  $extra  Columnas adicionales del modelo (ej. metodo_pago_id)
     */
    public function guardarQrPagoFacil(array $qr, array $extra = []): void
    {
        $this->update($extra + [
            'pago_facil_id_transaccion' => $qr['transaction_id'] ?? null,
            'pago_facil_numero_pago' => $qr['payment_number'] ?? null,
            'pago_facil_imagen_qr' => $qr['qr_image'] ?? null,
            'pago_facil_expira_en' => isset($qr['expiration']) ? Carbon::parse($qr['expiration']) : null,
            'pago_facil_estado' => $qr['status'] ?? 'pending',
            'pago_facil_respuesta_cruda' => null,
        ]);
    }

    /** Arma la respuesta del QR ya guardado, con el mismo formato que el service. */
    public function datosQrPagoFacil(float $monto, string $glosa): array
    {
        return [
            'success' => true,
            'transaction_id' => $this->pago_facil_id_transaccion,
            'payment_number' => $this->pago_facil_numero_pago,
            'qr_image' => $this->pago_facil_imagen_qr,
            'status' => 'pending',
            'monto' => $monto,
            'glosa' => $glosa,
            'expiration' => optional($this->pago_facil_expira_en)->toIso8601String(),
            // Un QR real es data-URI base64; el fallback simulado es una URL http externa.
            'simulado' => ! str_starts_with((string) $this->pago_facil_imagen_qr, 'data:'),
        ];
    }
}
