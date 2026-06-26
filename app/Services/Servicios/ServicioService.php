<?php

namespace App\Services\Servicios;

use App\Repositories\Servicios\ServicioRepository;
use App\Traits\RegistraBitacora;

class ServicioService
{
    use RegistraBitacora;

    public function __construct(protected ServicioRepository $repository) {}

    public function getAll()
    {
        return $this->repository->getAll();
    }

    public function getAllActivos()
    {
        return $this->repository->getAllActivos();
    }

    public function create(array $data)
    {
        $servicio = $this->repository->create($data);
        $this->registrarBitacora('crear', 'servicios', "Servicio creado: {$servicio->nombre}", null, $servicio->toArray());

        return $servicio;
    }

    public function update(int $id, array $data)
    {
        $anterior = $this->repository->findById($id);
        $this->repository->update($id, $data);
        $this->registrarBitacora('editar', 'servicios', "Servicio actualizado: {$anterior->nombre}", $anterior->toArray(), $data);

        return true;
    }

    public function delete(int $id)
    {
        $servicio = $this->repository->findById($id);
        $this->repository->delete($id);
        $this->registrarBitacora('eliminar', 'servicios', "Servicio eliminado: {$servicio->nombre}", $servicio->toArray());

        return true;
    }

    public function search(?string $term)
    {
        return $this->repository->search($term);
    }
}
