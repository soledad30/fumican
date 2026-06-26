<?php

namespace App\Services\Servicios;

use App\Repositories\Servicios\EspecieRepository;
use App\Traits\RegistraBitacora;
use DASPRiD\Enum\Exception\IllegalArgumentException;

class EspecieService
{
    use RegistraBitacora;

    public function __construct(protected EspecieRepository $repository) {}

    public function getAll()
    {
        return $this->repository->getAll();
    }

    public function getById($id)
    {
        return $this->repository->findById($id);
    }

    public function create(array $data)
    {
        $especie = $this->repository->create($data);
        $this->registrarBitacora('crear', 'especies', "Especie registrada: {$especie->nombre}", null, $especie->toArray());

        return $especie;
    }

    public function search()
    {
        return $this->repository->search();
    }

    public function update($id, array $data)
    {
        $anterior = $this->repository->findById($id);
        $this->repository->update($id, $data);
        $this->registrarBitacora('editar', 'especies', "Especie actualizada: {$anterior->nombre}", $anterior->toArray(), $data);

        return true;
    }

    public function delete($id)
    {
        $especie = $this->repository->findById($id);
        $this->repository->delete($id);
        $this->registrarBitacora('eliminar', 'especies', "Especie eliminada: {$especie->nombre}", $especie->toArray());

        return true;
    }

    public function findOrCreate($name)
    {
        if (empty($name)) {
            throw new IllegalArgumentException('Debe ingresar un nombre para la especie');
        }

        return $this->repository->findOrCreate($name);
    }

    public function listAll()
    {
        return $this->repository->listAll();
    }
}
