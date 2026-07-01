<?php

namespace App\Services\Usuarios;

use App\Repositories\Usuarios\RolRepository;
use Illuminate\Support\Collection;

class RolService
{
    public function __construct(protected RolRepository $repository) {}

    public function getAll()
    {
        return $this->repository->getAll();
    }

    public function getAllPaginated()
    {
        return $this->repository->getAllPaginated();
    }

    public function listarParaValidacion(): Collection
    {
        return $this->repository->listarParaValidacion();
    }

    public function buscarDuplicadoPorNombre(string $nombre, ?int $exceptId = null)
    {
        return $this->repository->buscarDuplicadoPorNombre($nombre, $exceptId);
    }

    public function buscarSimilares(string $nombre, ?int $exceptId = null): Collection
    {
        return $this->repository->buscarSimilares($nombre, $exceptId);
    }

    public function esRolProtegido(string $nombre): bool
    {
        return in_array(
            \App\Support\NormalizaNombre::rol($nombre),
            ['propietario', 'administrador'],
            true
        );
    }

    public function getById(string $id)
    {
        return $this->repository->getById($id);
    }

    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    public function update(string $id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function delete(string $id): bool
    {
        return $this->repository->delete($id);
    }
}
