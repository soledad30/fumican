<?php

namespace App\Repositories\Servicios;

use App\Models\Servicios\Mascota;
use App\Support\BusquedaTexto;

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
            $this->aplicarBusquedaTexto($query, $filters['search_term']);
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
        $query = $this->model->with(['owner', 'breed.specie']);
        $this->aplicarBusquedaTexto($query, $term);

        return $query->orderBy('nombre', 'asc')->take(10)->get();
    }

    private function aplicarBusquedaTexto($query, string $term): void
    {
        $query->where(function ($q) use ($term) {
            BusquedaTexto::whereLike($q, 'nombre', $term);
            $q->orWhereHas('owner', function ($qOwner) use ($term) {
                BusquedaTexto::whereLike($qOwner, 'nombre', $term);
                BusquedaTexto::whereLike($qOwner, 'apellido', $term, 'or');
                BusquedaTexto::whereLike($qOwner, 'ci', $term, 'or');
            });
        });
    }
}
