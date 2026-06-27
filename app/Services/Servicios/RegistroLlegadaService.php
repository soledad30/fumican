<?php

namespace App\Services\Servicios;

use App\Enums\EstadoConsultaEnum;
use App\Models\Servicios\ConsultaMedica;
use App\Support\RegistroClinica;
use InvalidArgumentException;

class RegistroLlegadaService
{
    public function __construct(
        protected ClienteService $clienteService,
        protected MascotaService $mascotaService,
        protected EspecieService $especieService,
        protected RazaService $razaService,
        protected ConsultaMedicaService $consultaMedicaService
    ) {}

    public function completarRegistroYAtender(int $consultaId, array $data, bool $emergencia = false): ConsultaMedica
    {
        $consulta = ConsultaMedica::with(['mascota.propietario', 'servicio'])->findOrFail($consultaId);

        if ($consulta->estado !== EstadoConsultaEnum::RESERVADA->value) {
            throw new InvalidArgumentException('Solo puede completar el registro en citas reservadas.');
        }

        $destinoEspera = EstadoConsultaEnum::EN_ESPERA->value;

        if (! RegistroClinica::requiereRegistroEnLlegada($consulta)) {
            return $this->consultaMedicaService->cambiarEstado($consultaId, $destinoEspera, $emergencia);
        }

        $cliente = $consulta->mascota?->propietario;
        $mascota = $consulta->mascota;

        if (! $cliente || ! $mascota) {
            throw new InvalidArgumentException('La consulta no tiene cliente o mascota asociados.');
        }

        $breedId = $data['breed_id'] ?? null;
        if (! $breedId && ! empty($data['specie']) && ! empty($data['breed'])) {
            $especie = $this->especieService->findOrCreate($data['specie']);
            $raza = $this->razaService->findOrCreate($data['breed'], $especie->id);
            $breedId = $raza->id;
        }

        if (! $breedId) {
            throw new InvalidArgumentException('Debe indicar la raza de la mascota.');
        }

        $this->clienteService->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'ci' => $data['ci'],
            'phone_number' => $data['phone_number'],
            'email' => $data['email'] ?? null,
            'gender' => $data['gender'],
            'address' => $data['address'] ?? null,
        ], $cliente->id);

        $this->mascotaService->update($mascota->id, [
            'name' => $data['pet_name'] ?? $mascota->nombre,
            'color' => $data['pet_color'],
            'breed_id' => $breedId,
            'gender' => $data['pet_gender'] ?? null,
            'age' => $data['pet_age'] ?? null,
            'weight' => $data['pet_weight'] ?? null,
            'customer_id' => $cliente->id,
        ]);

        return $this->consultaMedicaService->cambiarEstado(
            $consultaId,
            $destinoEspera,
            $emergencia
        );
    }
}
