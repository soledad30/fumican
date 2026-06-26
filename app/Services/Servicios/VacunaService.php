<?php

namespace App\Services\Servicios;

use App\Repositories\Servicios\VacunaRepository;
use App\Traits\RegistraBitacora;

class VacunaService
{
    use RegistraBitacora;

    public function __construct(protected VacunaRepository $repository) {}

    public function getAll()
    {
        return $this->repository->getAll();
    }

    public function getAllSinPaginar()
    {
        return $this->repository->getAllSinPaginar();
    }

    public function create(array $data)
    {
        $vacuna = $this->repository->create($data);
        $this->registrarBitacora('crear', 'vacunas', "Vacuna registrada: {$vacuna->nombre}", null, $vacuna->toArray());

        return $vacuna;
    }

    public function update(int $id, array $data)
    {
        $anterior = $this->repository->findById($id);
        $this->repository->update($id, $data);
        $this->registrarBitacora('editar', 'vacunas', "Vacuna actualizada: {$anterior->nombre}", $anterior->toArray(), $data);

        return true;
    }

    public function delete(int $id)
    {
        $vacuna = $this->repository->findById($id);
        $this->repository->delete($id);
        $this->registrarBitacora('eliminar', 'vacunas', "Vacuna eliminada: {$vacuna->nombre}", $vacuna->toArray());

        return true;
    }

    public function search(?string $term)
    {
        return $this->repository->search($term);
    }
}
