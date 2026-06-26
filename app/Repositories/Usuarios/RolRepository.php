<?php

namespace App\Repositories\Usuarios;

use App\Models\Usuarios\Rol;

class RolRepository
{
    public function __construct(protected Rol $model) {}

    public function getAll()
    {
        $query = $this->model->query()
            ->when(request()->boolean('with_permissions'), function ($query) {
                $query->with('permisos:id,nombre');
            })
            ->orderBy('actualizado_en', 'asc');

        if (request()->has('page')) {
            return $query->paginate();
        }

        return $query->get();
    }

    public function getAllPaginated()
    {
        return $this->model->query()
            ->with('permisos:id,nombre')
            ->orderBy('actualizado_en', 'asc')
            ->paginate();
    }

    public function getById($id)
    {
        return $this->model->findOrFail($id);
    }

    public function findByName($name)
    {
        return $this->model->where('nombre', $name)->first();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $role = $this->model->findOrFail($id);

        return $role->update($data);
    }

    public function delete($id): bool
    {
        return (bool) $this->model->where('id', $id)->delete();
    }
}
