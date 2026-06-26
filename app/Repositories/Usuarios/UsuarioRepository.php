<?php

namespace App\Repositories\Usuarios;

use App\Models\Usuario;

class UsuarioRepository
{
    public function __construct(protected Usuario $model) {}

    public function getAllPaginated()
    {
        return $this->model->with('rol:id,nombre')->orderBy('actualizado_en', 'desc')->paginate();
    }

    public function search(array $filters)
    {
        $query = $this->model->with('rol:id,nombre');

        if (! empty($filters['search_term'])) {
            $term = $filters['search_term'];
            $query->where(function ($q) use ($term) {
                $q->where('nombre', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        return $query->orderBy('actualizado_en', 'desc')->paginate()->appends($filters);
    }

    public function getById($id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $user = $this->model->findOrFail($id);

        return $user->update($data);
    }

    public function delete($id)
    {
        return $this->model->destroy($id);
    }
}
