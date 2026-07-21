<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  PagoFacilService — Pago electrónico por QR (PagoFácil Bolivia)
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Habla con el servicio boliviano PagoFácil para generar códigos
 *  QR de cobro (de una venta al contado o del pago de una cuota) y
 *  para consultar si el cliente ya pagó. Si el servicio real no
 *  responde (red del campus caída, sin credenciales), genera un QR
 *  "de mentira" local para que la pantalla no se rompa y el
 *  operador pueda confirmar el pago a mano.
 *
 *  IMPLEMENTACIÓN
 *  - Tipo: Service (App\Services). Integración HTTP con API externa.
 *  - Usa: Http (cliente HTTP de Laravel), Cache (guarda el Bearer
 *    token 1 hora), Log (diagnóstico), Str.
 *  - Configuración en config/services.php -> pagofacil (base_url,
 *    api_url, tc_token_service, tc_token_secret, callback_url,
 *    override_amount, response_language), leída del .env.
 *  - Flujo: obtenerBearerToken() -> obtenerHeaders() -> generateQr().
 *    generarQRVentaSimulado()/generarQRCuotaSimulado() arman el
 *    payload; consultarTransaccion()/verificarEstadoPago() revisan
 *    el estado; determinarEstadoPago() traduce el estado a
 *    completed/pending/cancelled/expired.
 *  - IMPORTANTE: las claves del payload (transactionId, paymentNumber,
 *    amount, currency=2 BOB, documentType=1 CI...) son el contrato
 *    de la API externa y van en inglés a propósito, no se traducen.
 *  - Fallback: generarQrSimuladoLocal() NO es un QR bancario real.
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PagoFacilService
{
    protected $baseUrl;

    protected $apiUrl;

    protected $tcTokenService;

    protected $tcTokenSecret;

    protected ?float $qrOverrideAmount = null;

    protected ?string $callbackUrl = null;

    protected ?string $responseLanguage = null;

    public function __construct()
    {
        $config = config('services.pagofacil', []);

        $this->baseUrl = $config['base_url'] ?? 'https://masterqr.pagofacil.com.bo';
        $this->apiUrl = $config['api_url'] ?? 'https://masterqr.pagofacil.com.bo/api/services/v2';
        $this->tcTokenService = $config['tc_token_service'] ?? null;
        $this->tcTokenSecret = $config['tc_token_secret'] ?? null;

        if (isset($config['override_amount']) && $config['override_amount'] !== null && $config['override_amount'] !== '') {
            $this->qrOverrideAmount = (float) $config['override_amount'];
        }

        $this->callbackUrl = $config['callback_url'] ?? null;
        if (! $this->callbackUrl) {
            $this->callbackUrl = rtrim(config('app.url'), '/').'/pagofacil/callback';
        }

        $this->responseLanguage = $config['response_language'] ?? 'es';
    }

    protected function obtenerBearerToken(): string
    {

        $tokenCacheKey = 'pagofacil_bearer_token';
        $cachedToken = Cache::get($tokenCacheKey);

        if ($cachedToken) {
            Log::info('🔑 [PagoFácil] Usando token en caché');

            return $cachedToken;
        }

        if (! $this->tcTokenService || ! $this->tcTokenSecret) {
            throw new \Exception('Las credenciales de PagoFácil no están configuradas. Verifica PAGOFACIL_TC_TOKEN_SERVICE y PAGOFACIL_TC_TOKEN_SECRET en .env');
        }

        try {
            Log::info('🔐 [PagoFácil] Autenticando para obtener Bearer token');
            $endpoint = "{$this->apiUrl}/login";

            Log::info("🔍 [PagoFácil] Intentando autenticación en: {$endpoint}");

            $headers = [
                'tcTokenService' => $this->tcTokenService,
                'tcTokenSecret' => $this->tcTokenSecret,
            ];

            if ($this->responseLanguage) {
                $headers['Response-Language'] = $this->responseLanguage;
            }

            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->post($endpoint);

            if ($response->successful()) {
                $data = $response->json();

                $token = $data['values']['accessToken'] ?? $data['accessToken'] ?? $data['token'] ?? $data['access_token'] ?? $data['data']['token'] ?? null;

                if ($token) {

                    Cache::put($tokenCacheKey, $token, now()->addHour());
                    Log::info('✅ [PagoFácil] Token obtenido exitosamente');

                    return $token;
                }

                throw new \Exception('No se encontró el token en la respuesta: '.json_encode($data));
            }

            throw new \Exception("Error al autenticar. Status {$response->status()}: {$response->body()}");
        } catch (\Exception $e) {
            Log::error('❌ [PagoFácil] Error al autenticar', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function obtenerHeaders(): array
    {
        $token = $this->obtenerBearerToken();
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$token,
        ];

        if ($this->responseLanguage) {
            $headers['Response-Language'] = $this->responseLanguage;
        }

        return $headers;
    }

    public function generateQr(array $datos): array
    {
        try {
            Log::info('🌐 [PagoFácil] Generando QR', ['datos' => $datos]);

            $headers = $this->obtenerHeaders();

            $response = Http::withHeaders($headers)
                ->post("{$this->apiUrl}/generate-qr", $datos);

            Log::info('📥 [PagoFácil] Respuesta recibida', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('✅ [PagoFácil] Respuesta exitosa de generate-qr', ['data' => $data]);

                $responseData = $data['values'] ?? $data;

                $result = [
                    'transactionId' => $responseData['transactionId'] ?? $responseData['transaction_id'] ?? null,
                    'qrBase64' => $responseData['qrBase64'] ?? $responseData['qr_base64'] ?? null,
                    'expirationDate' => $responseData['expirationDate'] ?? $responseData['expiration_date'] ?? null,
                ];

                Log::info('📊 [PagoFácil] Datos extraídos del QR', ['result' => $result]);

                return $result;
            }

            throw new \Exception('Error al generar QR: '.$response->body());
        } catch (\Exception $e) {
            Log::error('❌ [PagoFácil] Error al generar QR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function generarQRVentaSimulado($ventaId, $monto, $glosa = null)
    {
        try {

            $companyTransactionId = 'VENTA-'.$ventaId.'-'.time();

            Log::info('🔑 [PagoFácil] Generando QR para venta', [
                'venta_id' => $ventaId,
                'monto' => $monto,
                'company_transaction_id' => $companyTransactionId,
            ]);

            $baseUrl = rtrim(config('app.url'), '/');
            $callbackUrl = $baseUrl.'/webhook/pagofacil-simulado/venta';

            Log::info('🔗 [PagoFácil] URL de callback construida', [
                'app_url' => config('app.url'),
                'base_url' => $baseUrl,
                'callback_url' => $callbackUrl,
            ]);

            $montoQr = $this->resolverMontoQr($monto);

            $qrData = [
                'paymentMethod' => 34,
                'clientName' => 'Cliente',
                'documentType' => 1,
                'documentId' => '00000000',
                'phoneNumber' => '70000000',
                'email' => '',
                'paymentNumber' => $companyTransactionId,
                'amount' => $montoQr,
                'currency' => 2,
                'clientCode' => (string) $ventaId,
                'companyTransactionId' => $companyTransactionId,
                'callbackUrl' => $callbackUrl,
                'tcUrlCallBack' => $callbackUrl,
                'orderDetail' => [
                    [
                        'serial' => 1,
                        'product' => $glosa ?? "Venta #{$ventaId}",
                        'quantity' => 1,
                        'price' => $montoQr,
                        'discount' => 0,
                        'total' => $montoQr,
                    ],
                ],
            ];

            Log::info('📋 [PagoFácil] Datos preparados para venta', ['qr_data' => $qrData]);

            try {
                $response = $this->generateQr($qrData);

                Log::info('✅ [PagoFácil] QR generado exitosamente para venta', [
                    'response' => $response,
                    'tiene_transactionId' => isset($response['transactionId']),
                    'tiene_qrBase64' => isset($response['qrBase64']),
                ]);

                $qrImage = $response['qrBase64']
                    ? 'data:image/png;base64,'.$response['qrBase64']
                    : null;

                return [
                    'success' => true,
                    'transaction_id' => $response['transactionId'],
                    'payment_number' => $companyTransactionId,
                    'qr_image' => $qrImage,
                    'status' => 'pending',
                    'monto' => $monto,
                    'glosa' => $glosa ?? "Venta #{$ventaId}",
                    'expiration' => $response['expirationDate'] ?? now()->addHours(2)->toIso8601String(),
                ];
            } catch (\Exception $e) {
                Log::warning('⚠️ [PagoFácil] API real falló, consultando servicios habilitados...', [
                    'venta_id' => $ventaId,
                    'error' => $e->getMessage(),
                ]);
                $this->listarServiciosHabilitados();

                return $this->generarQrSimuladoLocal($companyTransactionId, $montoQr, $glosa ?? "Venta #{$ventaId}");
            }
        } catch (\Exception $e) {
            Log::error('❌ [PagoFácil] Error al generar QR para venta', [
                'venta_id' => $ventaId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function generarQRCuotaSimulado($cuotaId, $monto, $glosa = null)
    {
        try {

            $companyTransactionId = 'CUOTA-'.$cuotaId.'-'.time();

            Log::info('🔑 [PagoFácil] Generando QR para cuota', [
                'cuota_id' => $cuotaId,
                'monto' => $monto,
                'company_transaction_id' => $companyTransactionId,
            ]);

            $baseUrl = rtrim(config('app.url'), '/');
            $callbackUrl = $baseUrl.'/webhook/pagofacil-simulado/cuota';

            Log::info('🔗 [PagoFácil] URL de callback construida', [
                'app_url' => config('app.url'),
                'base_url' => $baseUrl,
                'callback_url' => $callbackUrl,
            ]);

            $montoQr = $this->resolverMontoQr($monto);

            $qrData = [
                'paymentMethod' => 34,
                'clientName' => 'Cliente',
                'documentType' => 1,
                'documentId' => '00000000',
                'phoneNumber' => '70000000',
                'email' => '',
                'paymentNumber' => $companyTransactionId,
                'amount' => $montoQr,
                'currency' => 2,
                'clientCode' => (string) $cuotaId,
                'companyTransactionId' => $companyTransactionId,
                'callbackUrl' => $callbackUrl,
                'tcUrlCallBack' => $callbackUrl,
                'orderDetail' => [
                    [
                        'serial' => 1,
                        'product' => $glosa ?? "Pago Cuota #{$cuotaId}",
                        'quantity' => 1,
                        'price' => $montoQr,
                        'discount' => 0,
                        'total' => $montoQr,
                    ],
                ],
            ];

            Log::info('📋 [PagoFácil] Datos preparados para cuota', ['qr_data' => $qrData]);

            try {
                $response = $this->generateQr($qrData);

                Log::info('✅ [PagoFácil] QR generado exitosamente para cuota', [
                    'response' => $response,
                    'tiene_transactionId' => isset($response['transactionId']),
                    'tiene_qrBase64' => isset($response['qrBase64']),
                ]);

                $qrImage = $response['qrBase64']
                    ? 'data:image/png;base64,'.$response['qrBase64']
                    : null;

                return [
                    'success' => true,
                    'transaction_id' => $response['transactionId'],
                    'payment_number' => $companyTransactionId,
                    'qr_image' => $qrImage,
                    'status' => 'pending',
                    'monto' => $monto,
                    'glosa' => $glosa ?? "Pago Cuota #{$cuotaId}",
                    'expiration' => $response['expirationDate'] ?? now()->addHours(2)->toIso8601String(),
                ];
            } catch (\Exception $e) {
                Log::warning('⚠️ [PagoFácil] API real falló, consultando servicios habilitados...', [
                    'cuota_id' => $cuotaId,
                    'error' => $e->getMessage(),
                ]);
                $this->listarServiciosHabilitados();

                return $this->generarQrSimuladoLocal($companyTransactionId, $montoQr, $glosa ?? "Pago Cuota #{$cuotaId}");
            }
        } catch (\Exception $e) {
            Log::error('❌ [PagoFácil] Error al generar QR para cuota', [
                'cuota_id' => $cuotaId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function consultarTransaccion(string $transactionId, ?string $paymentNumber = null): array
    {
        try {
            Log::info('🔍 [PagoFácil] Consultando transacción', [
                'pagofacil_transaction_id' => $transactionId,
            ]);

            $headers = $this->obtenerHeaders();

            $body = [
                'pagofacilTransactionId' => $transactionId,
                'companyTransactionId' => $paymentNumber ?? $transactionId,
            ];

            Log::info('📤 [PagoFácil] Enviando consulta', [
                'endpoint' => "{$this->apiUrl}/query-transaction",
                'body' => $body,
            ]);

            $response = Http::withHeaders($headers)
                ->post("{$this->apiUrl}/query-transaction", $body);

            Log::info('📥 [PagoFácil] Respuesta recibida', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('✅ [PagoFácil] Consulta exitosa', ['data' => $data]);

                return $data;
            }

            throw new \Exception('Error al consultar transacción: Status '.$response->status().' - '.$response->body());
        } catch (\Exception $e) {
            Log::error('❌ [PagoFácil] Error al consultar transacción', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function verificarEstadoPago($transactionId, ?string $paymentNumber = null)
    {
        try {
            $result = $this->consultarTransaccion($transactionId, $paymentNumber);

            $responseData = $this->extraerDatosRespuesta($result);
            $statusString = $this->determinarEstadoPago($responseData);

            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'status' => $statusString,
                'mensaje' => $statusString === 'completed'
                    ? 'Pago confirmado exitosamente'
                    : 'Pago pendiente de confirmación',
                'raw' => $responseData,
            ];
        } catch (\Exception $e) {
            Log::error('❌ [PagoFácil] Error al verificar estado', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'transaction_id' => $transactionId,
                'status' => 'pending',
                'mensaje' => 'Error al verificar estado: '.$e->getMessage(),
            ];
        }
    }

    public function simularConfirmacionPago($transactionId)
    {
        Log::info('Simulando confirmación de pago', ['transaction_id' => $transactionId]);

        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'status' => 'completed',
            'fecha_pago' => now()->toIso8601String(),
            'mensaje' => 'Pago simulado confirmado exitosamente',
        ];
    }

    public function validarWebhookSimulado($data)
    {
        $requiredFields = ['transaction_id', 'status'];

        foreach ($requiredFields as $field) {
            if (! isset($data[$field])) {
                Log::warning("Webhook simulado inválido: falta campo {$field}");

                return false;
            }
        }

        return true;
    }

    public function getTipoTransaccion($transactionId)
    {
        if (Str::startsWith($transactionId, 'PF-VENTA-') || Str::startsWith($transactionId, 'VENTA-')) {
            return 'venta';
        } elseif (Str::startsWith($transactionId, 'PF-CUOTA-') || Str::startsWith($transactionId, 'CUOTA-')) {
            return 'cuota';
        }

        return 'unknown';
    }

    protected function listarServiciosHabilitados(): void
    {
        try {
            $headers = $this->obtenerHeaders();
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->post("{$this->apiUrl}/list-enabled-services");

            if ($response->successful()) {
                $data = $response->json();
                Log::info('📋 [PagoFácil] Servicios habilitados', ['data' => $data]);
            } else {
                Log::warning('⚠️ [PagoFácil] Error al listar servicios', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('❌ [PagoFácil] Excepción al listar servicios', ['error' => $e->getMessage()]);
        }
    }

    protected function generarQrSimuladoLocal(string $transactionId, float $monto, string $glosa): array
    {
        $dataCodificada = urlencode("Pago RAO MOTOS - {$glosa} - {$transactionId}");
        $qrImage = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl={$dataCodificada}&choe=UTF-8";

        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'payment_number' => $transactionId,
            'qr_image' => $qrImage,
            'status' => 'pending',
            'monto' => $monto,
            'glosa' => $glosa,
            'expiration' => now()->addHours(2)->toIso8601String(),
            'simulado' => true,
        ];
    }

    protected function resolverMontoQr($montoOriginal): float
    {
        if ($this->qrOverrideAmount !== null) {
            return $this->qrOverrideAmount;
        }

        return (float) $montoOriginal;
    }

    protected function extraerDatosRespuesta(array $result): array
    {
        foreach (['values', 'data', 'response'] as $key) {
            if (isset($result[$key]) && is_array($result[$key])) {
                return $result[$key];
            }
        }

        return $result;
    }

    protected function determinarEstadoPago(array $responseData): string
    {
        $statusValue = $responseData['paymentStatus']
            ?? $responseData['status']
            ?? $responseData['transactionStatus']
            ?? $responseData['state']
            ?? null;

        if (is_null($statusValue)) {
            if (($responseData['approved'] ?? false) === true) {
                return 'completed';
            }

            return 'pending';
        }

        if (is_numeric($statusValue)) {
            $statusMap = [
                0 => 'pending',
                1 => 'pending',
                2 => 'completed',
                3 => 'cancelled',
                4 => 'expired',
            ];

            return $statusMap[(int) $statusValue] ?? 'pending';
        }

        return $this->mapearEstadoDesdeTexto((string) $statusValue);
    }

    protected function mapearEstadoDesdeTexto(string $status): string
    {
        $normalized = strtolower(trim($status));

        $completed = ['completed', 'complete', 'success', 'successful', 'paid', 'pagado', 'aprobado'];
        $cancelled = ['cancelled', 'canceled', 'rechazado', 'denied', 'failed', 'error'];
        $expired = ['expired', 'expirado', 'timeout', 'timeout_interrupted'];

        if (in_array($normalized, $completed, true)) {
            return 'completed';
        }

        if (in_array($normalized, $cancelled, true)) {
            return 'cancelled';
        }

        if (in_array($normalized, $expired, true)) {
            return 'expired';
        }

        return 'pending';
    }
}
