<?php

namespace App\Http\Controllers\Pagos;

use App\Http\Controllers\Controller;
use App\Services\Pagos\PaymentGatewayService;
use App\Support\ConsultaSaldo;
use App\Models\Servicios\Servicio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PagoFacilController extends Controller
{
    public function __construct(
        protected PaymentGatewayService $gateway,
    ) {}

    public function generarQr(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'documento' => 'nullable|string|max:30',
            'serviceId' => 'nullable|integer|exists:servicios,id',
            'monto' => 'nullable|numeric|min:0.01',
            'descripcion' => 'nullable|string|max:255',
        ]);

        try {
            $servicio = $request->filled('serviceId')
                ? Servicio::find($request->serviceId)
                : null;

            $precioServicio = (float) ($servicio?->precio ?? 0);
            $monto = $request->filled('monto')
                ? round((float) $request->monto, 2)
                : ConsultaSaldo::calcularAnticipo($precioServicio);
            $monto = max($monto, 0.01);

            $descripcion = $request->descripcion
                ?? $servicio?->nombre
                ?? 'Pago Fumican Vet';

            $resultado = $this->gateway->generarQrCobro([
                'nombre' => $request->name,
                'telefono' => $request->phone,
                'email' => $request->email ?: 'pagos@fumican.bo',
                'documento' => $request->documento ?? $request->phone,
                'monto' => $monto,
                'descripcion' => $descripcion,
            ]);

            return response()->json([
                'success' => true,
                'qrImage' => $resultado['qrImage'],
                'numeroTransaccion' => $resultado['transactionId'] !== null
                    ? (string) $resultado['transactionId']
                    : null,
                'numeroPago' => (string) $resultado['paymentNumber'],
                'monto' => $resultado['monto'],
                'montoAnticipo' => $resultado['monto'],
                'porcentajeAnticipo' => config('reservas.porcentaje_anticipo', 20),
                'expirationDate' => $resultado['expirationDate'],
            ]);
        } catch (\Throwable $th) {
            Log::error('Error al generar QR PagoFácil', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $th->getMessage() ?: 'No se pudo generar el código QR. Intente nuevamente.',
            ], 500);
        }
    }

    public function verificarPago(Request $request): JsonResponse
    {
        $request->merge([
            'numeroTransaccion' => $request->filled('numeroTransaccion')
                ? (string) $request->input('numeroTransaccion')
                : null,
            'numeroPago' => $request->filled('numeroPago')
                ? (string) $request->input('numeroPago')
                : null,
        ]);

        $request->validate([
            'numeroTransaccion' => 'nullable|string|max:100',
            'numeroPago' => 'nullable|string|max:100',
        ]);

        if (! $request->filled('numeroTransaccion') && ! $request->filled('numeroPago')) {
            return response()->json([
                'pagado' => false,
                'message' => 'Debe indicar numeroTransaccion o numeroPago.',
            ], 422);
        }

        try {
            $estado = $this->gateway->consultarEstado(
                $request->input('numeroPago'),
                $request->input('numeroTransaccion'),
            );

            return response()->json([
                'pagado' => $estado['pagado'],
                'paymentStatus' => $estado['paymentStatus'],
                'paymentInfo' => $estado['paymentInfo'],
                'data' => $estado['paymentInfo'],
            ]);
        } catch (\Throwable $th) {
            Log::warning('Error al verificar pago PagoFácil', [
                'message' => $th->getMessage(),
            ]);

            return response()->json([
                'pagado' => false,
                'message' => 'No se pudo verificar el pago.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function callback(Request $request): JsonResponse
    {
        try {
            Log::info('Callback PagoFácil recibido', ['data' => $request->all()]);

            $pedidoId = $request->input('PedidoID')
                ?? $request->input('paymentNumber')
                ?? $request->input('payment_number')
                ?? $request->input('nro_pago');

            $estado = $request->input('Estado')
                ?? $request->input('status')
                ?? $request->input('estado');

            $estadoAprobado = in_array(strtoupper((string) ($estado ?? '')), [
                'APROBADO', 'APPROVED', 'COMPLETED', 'PAID', 'SUCCESS', 'PAGADO',
            ], true) || in_array($estado, [1, '1', true], true);

            if ($pedidoId && $estadoAprobado) {
                $this->gateway->confirmarDesdeCallback((string) $pedidoId);
                Log::info('Pago QR confirmado vía callback', ['PedidoID' => $pedidoId]);
            }

            return response()->json([
                'error' => 0,
                'status' => 1,
                'message' => 'Notificación recibida',
                'values' => true,
            ]);
        } catch (\Throwable $th) {
            Log::error('Error en callback PagoFácil', [
                'message' => $th->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'error' => 1,
                'status' => 0,
                'message' => 'Error al procesar el callback: '.$th->getMessage(),
                'values' => false,
            ]);
        }
    }
}
