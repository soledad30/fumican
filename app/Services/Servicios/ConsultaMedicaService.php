<?php

namespace App\Services\Servicios;

use App\Enums\EstadoConsultaEnum;
use App\Models\Servicios\ConsultaMedica;
use App\Repositories\Servicios\ConsultaMedicaRepository;
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

        if ($destino === EstadoConsultaEnum::NO_ASISTIO && $consulta->fecha) {
            if (! $this->reservaYaVencio($consulta)) {
                throw new InvalidArgumentException('Solo puede marcar «No asistió» cuando ya pasó la fecha y hora de la cita.');
            }
        }

        if ($actual && ! $actual->puedeTransicionarA($destino)) {
            throw new InvalidArgumentException(
                'No se puede cambiar de «'.(EstadoConsultaEnum::labels()[$actual->value] ?? $actual->value)
                .'» a «'.EstadoConsultaEnum::labels()[$destino->value].'».'
            );
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
                if (! $this->reservaYaVencio($consulta)) {
                    return;
                }

                $consulta->update(['estado' => EstadoConsultaEnum::NO_ASISTIO->value]);
                $actualizadas++;
            });

        return $actualizadas;
    }

    private function reservaYaVencio(ConsultaMedica $consulta): bool
    {
        if (! $consulta->fecha) {
            return false;
        }

        $fecha = Carbon::parse($consulta->fecha)->format('Y-m-d');

        $hora = $consulta->hora
            ? Carbon::parse($consulta->hora)->format('H:i:s')
            : '23:59:59';

        $inicioReserva = Carbon::parse($fecha.' '.$hora);

        return $inicioReserva->isPast();
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
        }
        return $consultations;
    }
}
