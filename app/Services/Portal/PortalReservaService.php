<?php

namespace App\Services\Portal;

use App\Enums\EstadoConsultaEnum;
use App\Models\Servicios\Cliente;
use App\Models\Servicios\ConsultaMedica;
use App\Models\Servicios\Mascota;
use App\Models\Servicios\Servicio;
use Illuminate\Validation\ValidationException;

class PortalReservaService
{
    public function horariosDisponibles(): array
    {
        return ['09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00', '17:00'];
    }

    public function crearReserva(Cliente $cliente, array $data): ConsultaMedica
    {
        $mascota = Mascota::query()->find($data['mascota_id']);
        if (! $mascota || (int) $mascota->cliente_id !== (int) $cliente->id) {
            throw ValidationException::withMessages([
                'mascota_id' => 'La mascota seleccionada no pertenece a su cuenta.',
            ]);
        }

        $servicio = Servicio::query()
            ->where('id', $data['servicio_id'])
            ->where('esta_activo', true)
            ->first();

        if (! $servicio) {
            throw ValidationException::withMessages([
                'servicio_id' => 'El servicio seleccionado no está disponible.',
            ]);
        }

        $comentario = trim($data['comentario'] ?? '');
        $motivo = $comentario !== ''
            ? "Reserva: {$servicio->nombre}. {$comentario}"
            : "Reserva: {$servicio->nombre}";

        return ConsultaMedica::create([
            'fecha' => $data['fecha'],
            'hora' => $data['hora'],
            'motivo' => $motivo,
            'estado' => EstadoConsultaEnum::RESERVADA->value,
            'costo_consulta' => $servicio->precio ?? 0,
            'mascota_id' => $mascota->id,
            'usuario_id' => null,
            'servicio_id' => $servicio->id,
        ]);
    }
}
