<?php

namespace App\Services\Servicios;

use App\Repositories\Servicios\HistorialVacunacionRepository;
use App\Traits\RegistraBitacora;

class HistorialVacunacionService
{
    use RegistraBitacora;

    public function __construct(protected HistorialVacunacionRepository $repository) {}

    public function getAll()
    {
        return $this->repository->getAll();
    }

    public function create(array $data)
    {
        $registro = $this->repository->create($data);
        $this->registrarBitacora('crear', 'historial_vacunacion', 'Registro de vacunación creado', null, $registro->toArray());

        return $registro->load(['mascota', 'vacuna', 'veterinario']);
    }

    public function update(int $id, array $data)
    {
        $anterior = $this->repository->findById($id);
        $this->repository->update($id, $data);
        $this->registrarBitacora('editar', 'historial_vacunacion', 'Registro de vacunación actualizado', $anterior->toArray(), $data);

        return true;
    }

    public function delete(int $id)
    {
        $registro = $this->repository->findById($id);
        $this->repository->delete($id);
        $this->registrarBitacora('eliminar', 'historial_vacunacion', 'Registro de vacunación eliminado', $registro->toArray());

        return true;
    }

    public function search(?string $term)
    {
        return $this->repository->search($term);
    }
}
