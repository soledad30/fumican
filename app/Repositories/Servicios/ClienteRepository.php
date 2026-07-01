<?php

namespace App\Repositories\Servicios;

use App\Models\Servicios\Cliente;

class ClienteRepository
{
    public function __construct(protected Cliente $model) {}

    public function getAll()
    {
        return $this->model->orderBy('actualizado_en', 'desc')->paginate();
    }

    public function findById($id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $userData)
    {
        return $this->model->create($userData);
    }

    public function update(array $data, $id)
    {
        return $this->model->where('id', $id)->update($data);
    }

    public function delete($id)
    {
        return $this->model->destroy($id);
    }

    public function search(array $filters, bool $paginate = true)
    {
        $query = $this->model->query();

        if (! empty($filters['search_term'])) {
            $term = $filters['search_term'];
            $query->where(function ($q) use ($term) {
                $q->where('nombre', 'like', "%{$term}%")
                    ->orWhere('apellido', 'like', "%{$term}%")
                    ->orWhere('ci', 'like', "%{$term}%")
                    ->orWhere('telefono', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        $query->orderBy('actualizado_en', 'desc');

        if ($paginate) {
            return $query->paginate(15)->appends($filters);
        }

        $limit = (int) ($filters['limit'] ?? 15);

        return $query->take($limit)->get();
    }

    public function sinUsuario()
    {
        return $this->model
            ->whereNull('usuario_id')
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->get(['id', 'nombre', 'apellido', 'ci', 'telefono', 'fecha_nacimiento', 'direccion']);
    }
}
