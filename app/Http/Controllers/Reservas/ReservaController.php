<?php

namespace App\Http\Controllers\Reservas;

use App\Enums\EstadoConsultaEnum;
use App\Http\Controllers\Controller;
use App\Models\Servicios\Cliente;
use App\Models\Servicios\ConsultaMedica;
use App\Models\Servicios\Mascota;
use App\Models\Servicios\Pago;
use App\Models\Servicios\Servicio;
use App\Support\ConsultaSaldo;
use App\Traits\RegistraBitacora;
use Barryvdh\DomPDF\Facade\Pdf;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReservaController extends Controller
{
    use RegistraBitacora;

    public function reservePdf(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'petName' => 'required|string|max:255',
            'service' => 'required|string|max:255',
            'serviceId' => 'nullable|integer|exists:servicios,id',
            'date' => 'required|date',
            'timeSlot' => 'required|string|max:50',
            'comment' => 'nullable|string|max:500',
            'numeroTransaccion' => 'nullable|string|max:100',
            'montoAnticipo' => 'nullable|numeric|min:0',
        ]);

        $consulta = $this->persistirReserva($validated);

        $data = [
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'petName' => $validated['petName'],
            'service' => $validated['service'],
            'date' => $validated['date'],
            'timeSlot' => $validated['timeSlot'],
            'comment' => $validated['comment'] ?? 'Ninguno',
            'reserva_id' => $consulta?->id,
            'montoAnticipo' => $validated['montoAnticipo'] ?? null,
            'saldoPendiente' => $consulta?->saldo_pendiente,
        ];

        Log::info('Reserva registrada', ['consulta_id' => $consulta?->id, 'data' => $data]);

        $pdf = Pdf::loadView('pdf.reserve', compact('data'));

        return $pdf->download('reserva.pdf');
    }

    protected function persistirReserva(array $data): ?ConsultaMedica
    {
        try {
            $partes = preg_split('/\s+/', trim($data['name']), 2);
            $cliente = Cliente::firstOrCreate(
                ['ci' => $data['phone']],
                [
                    'nombre' => $partes[0] ?? $data['name'],
                    'apellido' => $partes[1] ?? '',
                    'telefono' => $data['phone'],
                    'email' => $data['email'] ?? null,
                ]
            );

            if (! empty($data['email']) && empty($cliente->email)) {
                $cliente->update(['email' => $data['email']]);
            }

            $mascota = Mascota::firstOrCreate(
                ['nombre' => $data['petName'], 'cliente_id' => $cliente->id],
                ['genero' => '0']
            );

            $servicio = ! empty($data['serviceId'])
                ? Servicio::find($data['serviceId'])
                : Servicio::where('nombre', 'like', '%'.$data['service'].'%')->first();

            $motivo = "Reserva: {$data['service']}. ".($data['comment'] ?? '');

            $consulta = ConsultaMedica::create([
                'fecha' => $data['date'],
                'hora' => $data['timeSlot'],
                'motivo' => $motivo,
                'estado' => EstadoConsultaEnum::RESERVADA->value,
                'costo_consulta' => $servicio?->precio ?? 0,
                'mascota_id' => $mascota->id,
                'usuario_id' => null,
                'servicio_id' => $servicio?->id,
            ]);

            $montoAnticipo = isset($data['montoAnticipo']) ? (float) $data['montoAnticipo'] : 0;
            if ($montoAnticipo > 0) {
                Pago::create([
                    'consulta_id' => $consulta->id,
                    'servicio_id' => $consulta->servicio_id,
                    'mascota_id' => $mascota->id,
                    'cliente_id' => $cliente->id,
                    'monto' => $montoAnticipo,
                    'metodo_pago' => 'qr',
                    'tipo_pago' => 'contado',
                    'concepto_pago' => 'anticipo',
                    'id_transaccion_externa' => $data['numeroTransaccion'] ?? null,
                    'fecha_pago' => now(),
                    'usuario_id' => null,
                ]);
            }

            return $consulta->fresh(['servicio', 'mascota', 'pagos']);
        } catch (\Throwable $e) {
            Log::warning('No se pudo persistir la reserva en BD', ['error' => $e->getMessage()]);

            return null;
        }
    }

    public function qr(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'nullable|email|max:255',
                'serviceId' => 'nullable|integer|exists:servicios,id',
                'monto' => 'nullable|numeric|min:0.01',
                'descripcion' => 'nullable|string|max:255',
            ]);

            $servicio = $request->filled('serviceId')
                ? Servicio::find($request->serviceId)
                : null;

            $precioServicio = (float) ($servicio?->precio ?? 0);
            $lnMontoClienteEmpresa = $request->filled('monto')
                ? round((float) $request->monto, 2)
                : ConsultaSaldo::calcularAnticipo($precioServicio);
            $lnMontoClienteEmpresa = max($lnMontoClienteEmpresa, 0.01);

            Log::info('request para generar QR', [
                'request' => $request->all(),
                'monto' => $lnMontoClienteEmpresa,
            ]);

            $lcComerceID = 'd029fa3a95e174a19934857f535eb9427d967218a36ea014b70ad704bc6c8d1c';
            $lnMoneda = 2;
            $lnTelefono = $request->phone;
            $lcNombreUsuario = $request->name;
            $lnCiNit = $request->phone;
            $lcNroPago = 'fumican-'.rand(100000, 999999);
            $lcCorreo = $request->email ?: 'pagos@fumican.bo';
            $lcUrlCallBack = 'https://tecnoweb.org.bo/inf513/grupo17sa';
            $lcUrlReturn = url('/');

            $nombreServicio = $request->descripcion
                ?? $servicio?->nombre
                ?? 'Pago consulta veterinaria';
            $laPedidoDetalle = [
                [
                    'Serial' => '1',
                    'Producto' => $nombreServicio,
                    'Cantidad' => 1,
                    'Precio' => $lnMontoClienteEmpresa,
                    'Descuento' => 0,
                    'Total' => $lnMontoClienteEmpresa,
                ],
            ];

            $lcUrl = 'https://serviciostigomoney.pagofacil.com.bo/api/servicio/generarqrv2';
            $laHeader = ['Accept' => 'application/json'];
            $laBody = [
                'tcCommerceID' => $lcComerceID,
                'tnMoneda' => $lnMoneda,
                'tnTelefono' => $lnTelefono,
                'tcNombreUsuario' => $lcNombreUsuario,
                'tnCiNit' => $lnCiNit,
                'tcNroPago' => $lcNroPago,
                'tnMontoClienteEmpresa' => $lnMontoClienteEmpresa,
                'tcCorreo' => $lcCorreo,
                'tcUrlCallBack' => $lcUrlCallBack,
                'tcUrlReturn' => $lcUrlReturn,
                'taPedidoDetalle' => $laPedidoDetalle,
            ];

            $loClient = new Client();
            $loResponse = $loClient->post($lcUrl, [
                'headers' => $laHeader,
                'json' => $laBody,
            ]);

            $laResult = json_decode($loResponse->getBody()->getContents());
            $laValues = explode(';', $laResult->values)[1];
            $laQrImage = json_decode($laValues)->qrImage;

            $laRawValues = explode(';', $laResult->values);
            $lcNumeroTransaccion = $laRawValues[0] ?? null;
            Log::info('transaccion QR', ['id' => $lcNumeroTransaccion, 'monto' => $lnMontoClienteEmpresa]);

            return response()->json([
                'success' => true,
                'qrImage' => $laQrImage,
                'numeroTransaccion' => $lcNumeroTransaccion,
                'montoAnticipo' => $lnMontoClienteEmpresa,
                'monto' => $lnMontoClienteEmpresa,
                'porcentajeAnticipo' => config('reservas.porcentaje_anticipo', 20),
            ]);
        } catch (\Throwable $th) {
            Log::error('Error al generar el QR', [
                'exception' => $th,
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo generar el código QR. Intente nuevamente.',
            ], 500);
        }
    }

    public function verificarPago(Request $request)
    {
        $request->validate([
            'numeroTransaccion' => 'required|string|max:100',
        ]);

        $numeroTransaccion = $request->numeroTransaccion;

        try {
            Log::info('verificando pago', [
                'numeroTransaccion' => $numeroTransaccion,
            ]);
            $client = new Client();
            $response = $client->post('https://serviciostigomoney.pagofacil.com.bo/api/servicio/consultartransaccion', [
                'headers' => ['Accept' => 'application/json'],
                'json' => ['TransaccionDePago' => $numeroTransaccion],
            ]);

            $data = json_decode($response->getBody()->getContents());

            return response()->json([
                'data' => $data->values,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'pagado' => false,
                'message' => 'No se pudo verificar el pago.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}
