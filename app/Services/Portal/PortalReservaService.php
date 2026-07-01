<?php

namespace App\Services\Portal;

use App\Enums\EstadoConsultaEnum;
use App\Models\Servicios\Cliente;
use App\Models\Servicios\ConsultaMedica;
use App\Models\Servicios\Mascota;
use App\Models\Servicios\Servicio;
use App\Models\Ventas\NotaVenta;
use App\Support\ConsultaSaldo;
use App\Support\NotaVentaSaldo;
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

    public function deudasCliente(Cliente $cliente): array
    {
        $mascotaIds = $cliente->mascotas()->pluck('id');

        $consultas = ConsultaMedica::query()
            ->with(['servicio:id,nombre', 'mascota:id,nombre'])
            ->whereIn('mascota_id', $mascotaIds)
            ->get()
            ->filter(fn (ConsultaMedica $c) => ConsultaSaldo::saldoPendiente($c) > 0.009)
            ->map(fn (ConsultaMedica $c) => [
                'tipo' => 'consulta',
                'id' => $c->id,
                'descripcion' => ($c->servicio?->nombre ?? 'Consulta').' — '.($c->mascota?->nombre ?? 'Mascota'),
                'saldo' => ConsultaSaldo::saldoPendiente($c),
                'fecha' => $c->fecha,
            ])
            ->values();

        $notas = NotaVenta::query()
            ->with('pagos')
            ->where('cliente_id', $cliente->id)
            ->get()
            ->filter(fn (NotaVenta $n) => NotaVentaSaldo::saldoPendiente($n) > 0.009)
            ->map(fn (NotaVenta $n) => [
                'tipo' => 'nota',
                'id' => $n->id,
                'descripcion' => 'Nota de venta #'.$n->id,
                'saldo' => NotaVentaSaldo::saldoPendiente($n),
                'fecha' => $n->fecha_venta,
            ])
            ->values();

        return $consultas->concat($notas)->sortByDesc('fecha')->values()->all();
    }

    public function comprasCliente(Cliente $cliente): array
    {
        return NotaVenta::query()
            ->where('cliente_id', $cliente->id)
            ->orderByDesc('fecha_venta')
            ->limit(20)
            ->get(['id', 'fecha_venta', 'monto_total'])
            ->map(fn (NotaVenta $n) => [
                'id' => $n->id,
                'fecha' => $n->fecha_venta,
                'total' => (float) ($n->monto_total ?? 0),
                'saldo' => NotaVentaSaldo::saldoPendiente($n->loadMissing('pagos')),
            ])
            ->all();
    }
}
