<?php

namespace App\Services\Pagos;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PaymentGatewayService
{
    private Client $client;

    private string $urlLogin;

    private string $urlListMethods;

    private string $urlQr;

    private string $urlQuery;

    private ?string $tokenService;

    private ?string $tokenSecret;

    private ?string $clientCode;

    private ?string $callbackUrl;

    private const CACHE_KEY_ACCESS_TOKEN = 'pagofacil_access_token';

    private const CACHE_KEY_TOKEN_EXPIRES = 'pagofacil_token_expires';

    private const CACHE_KEY_PAYMENT_METHOD_ID = 'pagofacil_payment_method_id';

    private const CACHE_PREFIX_PENDING = 'pagofacil_pending:';

    public function __construct()
    {
        $this->client = new Client(['timeout' => 30]);
        $this->urlLogin = config('pagofacil.urls.login');
        $this->urlListMethods = config('pagofacil.urls.list_methods');
        $this->urlQr = config('pagofacil.urls.generate_qr');
        $this->urlQuery = config('pagofacil.urls.query');
        $this->tokenService = config('pagofacil.token_service');
        $this->tokenSecret = config('pagofacil.token_secret');
        $this->clientCode = config('pagofacil.client_code');
        $this->callbackUrl = config('pagofacil.callback_url');
    }

    /**
     * @param  array{
     *     nombre: string,
     *     telefono: string,
     *     email?: string|null,
     *     documento?: string|null,
     *     monto: float,
     *     descripcion: string,
     *     order_detail?: array<int, array{producto: string, cantidad: int|float, precio: float, total: float}>
     * }  $datos
     * @return array{
     *     qrImage: string,
     *     transactionId: string|null,
     *     paymentNumber: string,
     *     monto: float,
     *     expirationDate: string|null
     * }
     */
    public function generarQrCobro(array $datos): array
    {
        if (! $this->tokenService || ! $this->tokenSecret || ! $this->clientCode) {
            throw new \RuntimeException(
                'Credenciales de PagoFácil no configuradas. Verifique PAGO_FACIL_TCTOKEN_SERVICE, PAGO_FACIL_TCTOKEN_SECRET y PAGO_FACIL_COMERCE_ID en .env'
            );
        }

        $monto = round((float) $datos['monto'], 2);
        if ($monto < 0.01) {
            throw new \InvalidArgumentException('El monto debe ser mayor a cero.');
        }

        $paymentNumber = $this->generarNumeroPago();
        $orderDetail = $this->prepararDetalleOrden($datos, $monto);
        $result = $this->solicitarQr([
            'paymentNumber' => $paymentNumber,
            'clientName' => $datos['nombre'],
            'documentId' => $datos['documento'] ?? $datos['telefono'],
            'phoneNumber' => $datos['telefono'],
            'email' => $datos['email'] ?? '',
            'amount' => $monto,
            'orderDetail' => $orderDetail,
        ]);

        if (isset($result->error) && (int) $result->error === 1) {
            throw new \RuntimeException($result->message ?? 'Error al generar QR en PagoFácil');
        }

        $qrImage = $this->extraerQrBase64($result);
        if (! $qrImage) {
            throw new \RuntimeException('PagoFácil no devolvió la imagen del código QR.');
        }

        $transactionId = $result->values->transactionId ?? $result->transactionId ?? null;
        $expirationDate = $result->values->expirationDate ?? null;

        $this->guardarPendiente($paymentNumber, [
            'transactionId' => $transactionId,
            'monto' => $monto,
            'descripcion' => $datos['descripcion'],
            'paid' => false,
        ]);

        return [
            'qrImage' => $qrImage,
            'transactionId' => $transactionId ? (string) $transactionId : null,
            'paymentNumber' => $paymentNumber,
            'monto' => $monto,
            'expirationDate' => $expirationDate,
        ];
    }

    /**
     * @return array{pagado: bool, paymentStatus: int|null, paymentInfo: array<string, mixed>|null}
     */
    public function consultarEstado(?string $companyTransactionId, ?string $pagofacilTransactionId): array
    {
        if ($companyTransactionId) {
            $pendiente = Cache::get(self::CACHE_PREFIX_PENDING.$companyTransactionId);
            if (is_array($pendiente) && ($pendiente['paid'] ?? false)) {
                return [
                    'pagado' => true,
                    'paymentStatus' => 1,
                    'paymentInfo' => ['companyTransactionId' => $companyTransactionId],
                ];
            }
        }

        $accessToken = $this->getAccessToken();
        $body = [];

        if ($pagofacilTransactionId) {
            $body['pagofacilTransactionId'] = $pagofacilTransactionId;
        } elseif ($companyTransactionId) {
            $body['companyTransactionId'] = $companyTransactionId;
        } else {
            throw new \InvalidArgumentException('Debe indicar numeroTransaccion o numeroPago.');
        }

        Log::info('Consultando estado PagoFácil', ['body' => $body]);

        $response = $this->client->post($this->urlQuery, [
            'headers' => $this->getApiHeaders($accessToken),
            'json' => $body,
        ]);

        $result = json_decode($response->getBody()->getContents());
        $paymentInfo = null;
        $paymentStatus = null;

        if (isset($result->values)) {
            $values = $result->values;
            $paymentStatus = isset($values->paymentStatus) ? (int) $values->paymentStatus : null;

            $paymentInfo = [
                'paymentStatus' => $paymentStatus,
                'paymentStatusDescription' => $values->paymentStatusDescription ?? null,
                'amount' => $values->amount ?? null,
                'currencyCode' => $values->currencyCode ?? 'BOB',
                'paymentMethodId' => $values->paymentMethodId ?? null,
                'paymentMethodDetail' => $values->paymentMethodDetail ?? null,
                'pagofacilTransactionId' => $values->pagofacilTransactionId ?? null,
                'companyTransactionId' => $values->companyTransactionId ?? null,
                'requestDate' => $values->requestDate ?? null,
                'requestTime' => $values->requestTime ?? null,
                'paymentDate' => $values->paymentDate ?? null,
                'paymentTime' => $values->paymentTime ?? null,
                'payerName' => $values->payerName ?? null,
                'payerDocument' => $values->payerDocument ?? null,
                'payerAccount' => $values->payerAccount ?? null,
                'payerBank' => $values->payerBank ?? null,
            ];

            if ($this->pagoEstaConfirmado(
                $paymentStatus,
                $values->paymentStatusDescription ?? null,
                $values->paymentDate ?? null,
                $values->paymentTime ?? null
            )) {
                $ref = $companyTransactionId
                    ?? ($values->companyTransactionId ?? null);
                if ($ref) {
                    $this->marcarPagado((string) $ref);
                }
            }
        }

        return [
            'pagado' => $paymentInfo
                ? $this->pagoEstaConfirmado(
                    $paymentStatus,
                    $paymentInfo['paymentStatusDescription'] ?? null,
                    $paymentInfo['paymentDate'] ?? null,
                    $paymentInfo['paymentTime'] ?? null
                )
                : false,
            'paymentStatus' => $paymentStatus,
            'paymentInfo' => $paymentInfo,
        ];
    }

    private function pagoEstaConfirmado(
        ?int $paymentStatus,
        ?string $description,
        ?string $paymentDate,
        ?string $paymentTime
    ): bool {
        if ($paymentStatus === 1) {
            return true;
        }

        $desc = strtoupper(trim((string) ($description ?? '')));
        if (in_array($desc, ['PAGADO', 'APROBADO', 'COMPLETED', 'PAID', 'SUCCESS'], true)) {
            return true;
        }

        return filled($paymentDate) && filled($paymentTime);
    }

    public function confirmarDesdeCallback(string $pedidoId): void
    {
        $this->marcarPagado($pedidoId);
    }

    private function marcarPagado(string $paymentNumber): void
    {
        $key = self::CACHE_PREFIX_PENDING.$paymentNumber;
        $pendiente = Cache::get($key, []);
        if (! is_array($pendiente)) {
            $pendiente = [];
        }
        $pendiente['paid'] = true;
        Cache::put($key, $pendiente, now()->addHours(4));
    }

    private function guardarPendiente(string $paymentNumber, array $data): void
    {
        Cache::put(self::CACHE_PREFIX_PENDING.$paymentNumber, $data, now()->addHours(4));
    }

    private function authenticate(): string
    {
        $response = $this->client->post($this->urlLogin, [
            'headers' => [
                'Content-Type' => 'application/json',
                'tcTokenService' => $this->tokenService,
                'tcTokenSecret' => $this->tokenSecret,
            ],
        ]);

        $result = json_decode($response->getBody()->getContents());

        if (isset($result->error) && (int) $result->error === 1) {
            throw new \RuntimeException('Error en autenticación PagoFácil: '.($result->message ?? 'desconocido'));
        }

        if (! isset($result->values->accessToken)) {
            throw new \RuntimeException('PagoFácil no devolvió accessToken.');
        }

        $accessToken = $result->values->accessToken;
        $expiresInMinutes = (int) ($result->values->expiresInMinutes ?? 200);
        $cacheTime = max(60, ($expiresInMinutes - 5) * 60);

        Cache::put(self::CACHE_KEY_ACCESS_TOKEN, $accessToken, $cacheTime);
        Cache::put(self::CACHE_KEY_TOKEN_EXPIRES, now()->addMinutes($expiresInMinutes), $cacheTime);

        return $accessToken;
    }

    private function getAccessToken(): string
    {
        $accessToken = Cache::get(self::CACHE_KEY_ACCESS_TOKEN);
        $expiresAt = Cache::get(self::CACHE_KEY_TOKEN_EXPIRES);

        if (! $accessToken || ! $expiresAt || now()->greaterThan($expiresAt)) {
            return $this->authenticate();
        }

        return $accessToken;
    }

    /**
     * @return array<string, string>
     */
    private function getApiHeaders(string $accessToken): array
    {
        return [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$accessToken,
            'Response-Language' => 'es',
        ];
    }

    private function getPaymentMethodId(bool $forceRefresh = false): int
    {
        if (! $forceRefresh) {
            $cachedId = Cache::get(self::CACHE_KEY_PAYMENT_METHOD_ID);
            if ($cachedId !== null) {
                return (int) $cachedId;
            }
        }

        $configuredId = config('pagofacil.payment_method_id');
        if ($configuredId !== null && $configuredId !== '') {
            return (int) $configuredId;
        }

        $accessToken = $this->getAccessToken();
        $response = $this->client->post($this->urlListMethods, [
            'headers' => $this->getApiHeaders($accessToken),
        ]);

        $result = json_decode($response->getBody()->getContents());

        if (isset($result->error) && (int) $result->error === 1) {
            throw new \RuntimeException($result->message ?? 'Error al listar métodos de pago');
        }

        if (! isset($result->values) || ! is_array($result->values) || count($result->values) === 0) {
            throw new \RuntimeException('No hay métodos de pago QR habilitados en PagoFácil.');
        }

        $paymentMethodId = null;
        foreach ($result->values as $method) {
            $name = $method->paymentMethodName ?? '';
            if (stripos($name, 'QR') !== false) {
                $paymentMethodId = (int) $method->paymentMethodId;
                break;
            }
        }

        if ($paymentMethodId === null) {
            $paymentMethodId = (int) $result->values[0]->paymentMethodId;
        }

        Cache::put(self::CACHE_KEY_PAYMENT_METHOD_ID, $paymentMethodId, 86400);

        return $paymentMethodId;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function solicitarQr(array $payload, bool $retry = false): object
    {
        $accessToken = $this->getAccessToken();
        $paymentMethodId = $this->getPaymentMethodId($retry);

        $body = [
            'paymentMethod' => $paymentMethodId,
            'clientName' => $payload['clientName'],
            'documentType' => 1,
            'documentId' => (string) $payload['documentId'],
            'phoneNumber' => (string) $payload['phoneNumber'],
            'email' => (string) ($payload['email'] ?? ''),
            'paymentNumber' => (string) $payload['paymentNumber'],
            'amount' => (float) $payload['amount'],
            'currency' => 2,
            'clientCode' => $this->clientCode,
            'callbackUrl' => $this->callbackUrl ?? url('/pago-facil/callback'),
            'orderDetail' => $payload['orderDetail'],
        ];

        Log::info('Generando QR PagoFácil v2', [
            'paymentNumber' => $body['paymentNumber'],
            'amount' => $body['amount'],
        ]);

        try {
            $response = $this->client->post($this->urlQr, [
                'headers' => $this->getApiHeaders($accessToken),
                'json' => $body,
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null;

            if (! $retry && $responseBody && stripos($responseBody, 'Payment Method ID') !== false) {
                Cache::forget(self::CACHE_KEY_PAYMENT_METHOD_ID);

                return $this->solicitarQr($payload, true);
            }

            $apiMessage = $responseBody
                ? (json_decode($responseBody)->message ?? $responseBody)
                : $e->getMessage();

            throw new \RuntimeException('Error al comunicarse con PagoFácil: '.$apiMessage);
        }
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<int, array{serial: int, product: string, quantity: int|float, price: float, discount: int, total: float}>
     */
    private function prepararDetalleOrden(array $datos, float $monto): array
    {
        if (! empty($datos['order_detail']) && is_array($datos['order_detail'])) {
            $items = [];
            $serial = 1;
            foreach ($datos['order_detail'] as $detalle) {
                $items[] = [
                    'serial' => $serial++,
                    'product' => (string) ($detalle['producto'] ?? 'Servicio'),
                    'quantity' => (float) ($detalle['cantidad'] ?? 1),
                    'price' => (float) ($detalle['precio'] ?? $monto),
                    'discount' => 0,
                    'total' => (float) ($detalle['total'] ?? $monto),
                ];
            }

            return $items;
        }

        return [[
            'serial' => 1,
            'product' => (string) ($datos['descripcion'] ?? 'Pago Fumican Vet'),
            'quantity' => 1,
            'price' => $monto,
            'discount' => 0,
            'total' => $monto,
        ]];
    }

    private function extraerQrBase64(object $result): ?string
    {
        if (isset($result->values->qrBase64)) {
            return $result->values->qrBase64;
        }
        if (isset($result->qrBase64)) {
            return $result->qrBase64;
        }
        if (isset($result->values->qrImage)) {
            return $result->values->qrImage;
        }
        if (isset($result->data->qrBase64)) {
            return $result->data->qrBase64;
        }
        if (isset($result->qrImage)) {
            return $result->qrImage;
        }

        return null;
    }

    private function generarNumeroPago(): string
    {
        return 'FUM'.random_int(100000000, 999999999);
    }
}
