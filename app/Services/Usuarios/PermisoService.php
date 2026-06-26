<?php

namespace App\Services\Usuarios;

use App\Repositories\Usuarios\PermisoRepository;

class PermisoService
{
    public function __construct(protected PermisoRepository $repository) {}

    public function getAll()
    {
        return $this->repository->getAll();
    }
}
