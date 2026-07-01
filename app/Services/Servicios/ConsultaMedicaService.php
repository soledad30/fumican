<?php

namespace App\Services\Servicios;

use App\Enums\EstadoConsultaEnum;
use App\Models\Servicios\ConsultaMedica;
use App\Models\Servicios\Pago;
use App\Repositories\Servicios\ConsultaMedicaRepository;
use App\Support\PoliticaReserva;
use App\Support\RegistroClinica;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class ConsultaMedicaService
{
    public function __construct(protected ConsultaMedicaRepository $repository) {}

    public function getAllWithDetails()
    {
        $this->marcarReservasVencidasComoNoAsistio();

        $medicalConsultations = $this->repository->getAllWithDetails();

        return $this->addPetDetailsToConsultations($medicalConsultations);
    }

    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    public function update(array $data, $id)
    {
        return $this->repository->update($data, $id);
    }

    public function delete($id)
    {
        return $this->repository->delete($id);
    }

    public function search(array $filters)
    {
        $this->marcarReservasVencidasComoNoAsistio();

        $medicalConsultations = $this->repository->search($filters, true);

        return $this->addPetDetailsToConsultations($medicalConsultations);
    }

    public function getFilteredResults(array $filters)
    {
        $consultations = $this->repository->search($filters, false); // Not paginated
        // Asegurarse de que los resultados para el PDF también tengan los detalles
        return $this->addPetDetailsToConsultations($consultations);
    }

    public function cambiarEstado(int $id, string $nuevoEstado, bool $emergencia = false): ConsultaMedica
    {
        $consulta = ConsultaMedica::findOrFail($id);
        $actual = EstadoConsultaEnum::tryFrom($consulta->estado ?? '');
        $destino = EstadoConsultaEnum::from($nuevoEstado);

        if ($destino === EstadoConsultaEnum::EN_ATENCION && $consulta->fecha && ! $emergencia) {
            $fechaReserva = Carbon::parse($consulta->fecha)->toDateString();
            if ($fechaReserva !== Carbon::today()->toDateString()) {
                throw new InvalidArgumentException('Fuera de fecha de atención, vuelva.');
            }
        }

        if ($destino === EstadoConsultaEnum::EN_ESPERA && $consulta->fecha && ! $emergencia) {
            $fechaReserva = Carbon::parse($consulta->fecha)->toDateString();
            if ($fechaReserva !== Carbon::today()->toDateString()) {
                throw new InvalidArgumentException('El check-in solo puede realizarse el día de la cita.');
            }
        }

        if ($destino === EstadoConsultaEnum::NO_ASISTIO && $consulta->fecha) {
            if (! PoliticaReserva::puedeMarcarNoAsistio($consulta)) {
                throw new InvalidArgumentException(
                    'Solo puede marcar «No asistió» después de la hora de la cita más el tiempo de gracia, sin check-in.'
                );
            }
        }

        if ($actual && ! $actual->puedeTransicionarA($destino)) {
            throw new InvalidArgumentException(
                'No se puede cambiar de «'.(EstadoConsultaEnum::labels()[$actual->value] ?? $actual->value)
                .'» a «'.EstadoConsultaEnum::labels()[$destino->value].'».'
            );
        }

        if ($destino === EstadoConsultaEnum::NO_ASISTIO) {
            $this->marcarAnticipoPerdido($consulta);
        }

        $datos = ['estado' => $destino->value];

        if (in_array($destino, [EstadoConsultaEnum::EN_ATENCION, EstadoConsultaEnum::COMPLETADA], true)
            && empty($consulta->usuario_id)) {
            $datos['usuario_id'] = Auth::id();
        }

        $this->repository->update($datos, $id);

        return $consulta->fresh(['servicio', 'mascota.propietario', 'veterinario']);
    }

    public function marcarReservasVencidasComoNoAsistio(): int
    {
        $actualizadas = 0;

        ConsultaMedica::query()
            ->where('estado', EstadoConsultaEnum::RESERVADA->value)
            ->whereNotNull('fecha')
            ->each(function (ConsultaMedica $consulta) use (&$actualizadas) {
                if (! PoliticaReserva::ventanaNoAsistioCumplida($consulta)) {
                    return;
                }

                $consulta->update(['estado' => EstadoConsultaEnum::NO_ASISTIO->value]);
                $this->marcarAnticipoPerdido($consulta);
                $actualizadas++;
            });

        return $actualizadas;
    }

    private function reservaYaVencio(ConsultaMedica $consulta): bool
    {
        return PoliticaReserva::horaCitaPasada($consulta);
    }

    public function registrarLlegada(int $id, bool $emergencia = false): ConsultaMedica
    {
        $consulta = ConsultaMedica::with(['mascota.propietario'])->findOrFail($id);

        if (RegistroClinica::requiereRegistroEnLlegada($consulta)) {
            throw new InvalidArgumentException(
                'Debe completar el registro del propietario y la mascota antes del check-in.'
            );
        }

        return $this->cambiarEstado($id, EstadoConsultaEnum::EN_ESPERA->value, $emergencia);
    }

    public function reprogramar(int $id, string $fecha, string $hora): ConsultaMedica
    {
        $consulta = ConsultaMedica::with(['servicio', 'mascota.propietario', 'pagos'])->findOrFail($id);

        if ($consulta->estado !== EstadoConsultaEnum::NO_ASISTIO->value) {
            throw new InvalidArgumentException('Solo puede reprogramar citas marcadas como «No asistió».');
        }

        $motivo = trim((string) ($consulta->motivo ?? ''));
        $nota = ' [Reprogramada el '.now()->format('d/m/Y H:i').' — sin anticipo previo]';

        $this->repository->update([
            'fecha' => $fecha,
            'hora' => $hora,
            'estado' => EstadoConsultaEnum::RESERVADA->value,
            'motivo' => $motivo.$nota,
        ], $id);

        return $consulta->fresh(['servicio', 'mascota.propietario', 'veterinario', 'pagos']);
    }

    public function reprogramarPorTarde(int $id, string $fecha, string $hora): ConsultaMedica
    {
        $consulta = ConsultaMedica::with(['servicio', 'mascota.propietario', 'pagos'])->findOrFail($id);

        if (! PoliticaReserva::puedeReprogramarTarde($consulta)) {
            throw new InvalidArgumentException(
                'Solo puede reprogramar por llegada tarde una vez, el mismo día de la cita y antes del cierre de la clínica.'
            );
        }

        $motivo = trim((string) ($consulta->motivo ?? ''));
        $nota = ' [Reprogramada por llegada tarde el '.now()->format('d/m/Y H:i').' — anticipo conservado]';

        $this->repository->update([
            'fecha' => $fecha,
            'hora' => $hora,
            'estado' => EstadoConsultaEnum::RESERVADA->value,
            'reprogramada_tarde' => true,
            'motivo' => $motivo.$nota,
        ], $id);

        return $consulta->fresh(['servicio', 'mascota.propietario', 'veterinario', 'pagos']);
    }

    public function getAgendaDia(?string $fecha = null): array
    {
        $this->marcarReservasVencidasComoNoAsistio();

        $fecha = $fecha ?? Carbon::today()->toDateString();
        $consultas = $this->addPetDetailsToConsultations($this->repository->getByFecha($fecha));

        $columnas = [
            EstadoConsultaEnum::RESERVADA->value => [],
            EstadoConsultaEnum::EN_ESPERA->value => [],
            EstadoConsultaEnum::EN_ATENCION->value => [],
            EstadoConsultaEnum::COMPLETADA->value => [],
            EstadoConsultaEnum::NO_ASISTIO->value => [],
        ];

        foreach ($consultas as $consulta) {
            $estado = $consulta->estado;
            if (array_key_exists($estado, $columnas)) {
                $columnas[$estado][] = $consulta;
            }
        }

        return [
            'fecha' => $fecha,
            'columnas' => $columnas,
            'total' => $consultas->count(),
        ];
    }

    public function getRecordatorios(?string $fecha = null)
    {
        $fecha = $fecha ?? Carbon::tomorrow()->toDateString();

        return $this->addPetDetailsToConsultations(
            $this->repository->getByFecha($fecha, [EstadoConsultaEnum::RESERVADA->value])
        );
    }

    public function iniciarAtencion(int $mascotaId, ?int $servicioId = null, ?string $motivo = null): ConsultaMedica
    {
        $servicio = $servicioId ? \App\Models\Servicios\Servicio::find($servicioId) : null;

        $consulta = $this->repository->create([
            'mascota_id' => $mascotaId,
            'motivo' => $motivo ?: 'Consulta en atención',
            'estado' => EstadoConsultaEnum::EN_ATENCION->value,
            'fecha' => Carbon::today()->toDateString(),
            'hora' => now()->format('H:i:s'),
            'usuario_id' => Auth::id(),
            'servicio_id' => $servicio?->id,
            'costo_consulta' => $servicio?->precio ?? 0,
        ]);

        return $consulta->fresh(['servicio', 'mascota.propietario', 'veterinario', 'pagos']);
    }

    public function finalizarAtencion(int $id, array $datosClinicos): ConsultaMedica
    {
        $consulta = ConsultaMedica::findOrFail($id);
        $datosClinicos['estado'] = EstadoConsultaEnum::COMPLETADA->value;

        if (empty($datosClinicos['fecha'])) {
            $datosClinicos['fecha'] = $consulta->fecha ?? Carbon::today()->toDateString();
        }

        $this->repository->update($datosClinicos, $id);

        return $consulta->fresh(['servicio', 'mascota.propietario', 'veterinario', 'pagos']);
    }

    private function marcarAnticipoPerdido(ConsultaMedica $consulta): void
    {
        Pago::query()
            ->where('consulta_id', $consulta->id)
            ->where('concepto_pago', 'anticipo')
            ->update(['concepto_pago' => 'anticipo_perdido']);
    }

    /**
     * AÑADIDO: Método privado para centralizar la lógica de añadir detalles.
     * Funciona tanto para colecciones paginadas como para colecciones normales.
     */
    private function addPetDetailsToConsultations($consultations)
    {
        foreach ($consultations as $mc) {
            $mc->pet_name = $mc->mascota?->nombre ?? $mc->mascota?->name ?? $mc->pet?->name ?? 'N/A';
            $propietario = $mc->mascota?->propietario ?? $mc->mascota?->owner ?? $mc->pet?->owner;
            $mc->pet_owner = $propietario
                ? trim(($propietario->nombre ?? $propietario->first_name ?? '').' '.($propietario->apellido ?? $propietario->last_name ?? ''))
                : 'N/A';
            $mc->requiere_registro_llegada = $mc->estado === EstadoConsultaEnum::RESERVADA->value
                && RegistroClinica::requiereRegistroEnLlegada($mc);
            $mc->puede_reprogramar_tarde = PoliticaReserva::puedeReprogramarTarde($mc);
            $mc->puede_marcar_no_asistio = PoliticaReserva::puedeMarcarNoAsistio($mc);
            $mc->saldo_pendiente = \App\Support\ConsultaSaldo::saldoPendiente($mc);
            $mc->monto_pagado = \App\Support\ConsultaSaldo::montoPagado($mc);
        }
        return $consultations;
    }
}
