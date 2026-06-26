<?php

namespace App\Repositories\Usuarios;

use App\Models\Usuarios\Permiso;

class PermisoRepository
{
    public function __construct(protected Permiso $model) {}

    public function getAll()
    {
        $query = $this->model->query()
            ->when(request()->boolean('with_roles'), function ($query) {
                $query->with('roles:id,nombre');
            })
            ->orderBy('nombre', 'asc');

        return $query->get();
    }

    public function findById($id)
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
        return $this->model->where('id', $id)->update($data);
    }
}
