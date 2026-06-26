<?php

namespace App\Services\Servicios;

use App\Repositories\Servicios\RazaRepository;
use App\Traits\RegistraBitacora;
use DASPRiD\Enum\Exception\IllegalArgumentException;

class RazaService
{
    use RegistraBitacora;

    public function __construct(protected RazaRepository $repository) {}

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
        $raza = $this->repository->create($data);
        $this->registrarBitacora('crear', 'razas', "Raza registrada: {$raza->nombre}", null, $raza->toArray());

        return $raza;
    }

    public function search()
    {
        return $this->repository->search();
    }

    public function update($id, array $data)
    {
        $anterior = $this->repository->findById($id);
        $this->repository->update($id, $data);
        $this->registrarBitacora('editar', 'razas', "Raza actualizada: {$anterior->nombre}", $anterior->toArray(), $data);

        return true;
    }

    public function delete($id)
    {
        $raza = $this->repository->findById($id);
        $this->repository->delete($id);
        $this->registrarBitacora('eliminar', 'razas', "Raza eliminada: {$raza->nombre}", $raza->toArray());

        return true;
    }

    public function findOrCreate($name, $specieId)
    {
        if (empty($name)) {
            throw new IllegalArgumentException('Debe ingresar un nombre para la raza');
        }

        return $this->repository->findOrCreate($name, $specieId);
    }
}
