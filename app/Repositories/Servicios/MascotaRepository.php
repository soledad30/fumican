<?php

namespace App\Repositories\Servicios;

use App\Models\Servicios\Mascota;

class MascotaRepository
{
    public function __construct(protected Mascota $model) {}

    public function getAll()
    {
        return $this->model
            ->with(['owner', 'breed.specie'])
            ->orderBy('actualizado_en', 'desc')
            ->paginate();
    }

    public function findById($id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $pet = $this->model->findOrFail($id);

        return $pet->update($data);
    }

    public function delete($id)
    {
        return $this->model->destroy($id);
    }

    public function searchWithFilters(array $filters)
    {
        $query = $this->model->with(['owner', 'breed.specie']);

        if (! empty($filters['search_term'])) {
            $term = $filters['search_term'];
            $query->where(function ($q) use ($term) {
                $q->where('nombre', 'like', "%{$term}%")
                    ->orWhereHas('owner', function ($qOwner) use ($term) {
                        $qOwner->where('nombre', 'like', "%{$term}%")
                            ->orWhere('apellido', 'like', "%{$term}%")
                            ->orWhere('ci', 'like', "%{$term}%");
                    });
            });
        }

        return $query->orderBy('actualizado_en', 'desc')->paginate()->appends($filters);
    }

    public function listForSelect()
    {
        return $this->model
            ->with('owner:id,nombre,apellido')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'cliente_id']);
    }

    public function autocompleteSearch(string $term)
    {
        return $this->model
            ->with(['owner', 'breed.specie'])
            ->where(function ($q) use ($term) {
                $q->where('nombre', 'like', "%{$term}%")
                    ->orWhereHas('owner', function ($qOwner) use ($term) {
                        $qOwner->where('nombre', 'like', "%{$term}%")
                            ->orWhere('apellido', 'like', "%{$term}%")
                            ->orWhere('ci', 'like', "%{$term}%");
                    });
            })
            ->orderBy('nombre', 'asc')
            ->take(10)
            ->get();
    }
}
