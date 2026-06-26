<?php

namespace App\Services\Usuarios;

use App\Repositories\Usuarios\RolRepository;

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
