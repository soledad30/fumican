<?php

namespace App\Http\Controllers\Reservas;

use App\Enums\EstadoConsultaEnum;
use App\Http\Controllers\Controller;
use App\Models\Servicios\Cliente;
use App\Models\Servicios\ConsultaMedica;
use App\Models\Servicios\Mascota;
use App\Models\Servicios\Pago;
use App\Models\Servicios\Servicio;
use App\Traits\RegistraBitacora;
use Barryvdh\DomPDF\Facade\Pdf;
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
}
