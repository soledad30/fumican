<?php

namespace App\Repositories\Servicios;

use App\Models\Servicios\ConsultaMedica;

class ConsultaMedicaRepository
{
    public function __construct(protected ConsultaMedica $model) {}

    public function getAllWithDetails()
    {
        return $this->model
            ->with(['mascota.propietario', 'mascota.raza.especie', 'veterinario', 'servicio', 'pagos'])
            ->orderBy('actualizado_en', 'desc')
            ->paginate();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(array $data, $id)
    {
        $mc = $this->model->findOrFail($id);

        return $mc->update($data);
    }

    public function delete($id)
    {
        return $this->model->destroy($id);
    }

    public function search(array $filters, bool $paginate = true)
    {
        $query = $this->model->with(['mascota.propietario', 'mascota.raza.especie', 'veterinario', 'servicio', 'pagos']);

        if (! empty($filters['search_term'])) {
            $term = $filters['search_term'];
            $query->where(function ($q) use ($term) {
                $q->where('motivo', 'like', "%{$term}%")
                    ->orWhereHas('pet', function ($qPet) use ($term) {
                        $qPet->where('nombre', 'like', "%{$term}%")
                            ->orWhereHas('owner', function ($qOwner) use ($term) {
                                $qOwner->where('nombre', 'like', "%{$term}%")
                                    ->orWhere('apellido', 'like', "%{$term}%");
                            });
                    });
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('creado_en', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('creado_en', '<=', $filters['date_to']);
        }

        if (! empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        $query->orderBy('creado_en', 'desc');

        if ($paginate) {
            return $query->paginate()->appends($filters);
        }

        return $query->get();
    }
}
