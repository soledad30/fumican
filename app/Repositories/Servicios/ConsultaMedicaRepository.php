<?php

namespace App\Repositories\Servicios;

use App\Models\Servicios\ConsultaMedica;
use App\Support\BusquedaTexto;

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
                BusquedaTexto::whereLike($q, 'motivo', $term);
                $q->orWhereHas('pet', function ($qPet) use ($term) {
                    BusquedaTexto::whereLike($qPet, 'nombre', $term);
                    $qPet->orWhereHas('owner', function ($qOwner) use ($term) {
                        BusquedaTexto::whereLike($qOwner, 'nombre', $term);
                        BusquedaTexto::whereLike($qOwner, 'apellido', $term, 'or');
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

        if (! empty($filters['fecha'])) {
            $query->whereDate('fecha', $filters['fecha']);
        }

        if (! empty($filters['servicio_id'])) {
            $query->where('servicio_id', $filters['servicio_id']);
        }

        if (! empty($filters['propietario'])) {
            $term = $filters['propietario'];
            $query->whereHas('mascota.propietario', function ($q) use ($term) {
                BusquedaTexto::whereLike($q, 'nombre', $term);
                BusquedaTexto::whereLike($q, 'apellido', $term, 'or');
            });
        }

        if (! empty($filters['mascota'])) {
            $term = $filters['mascota'];
            $query->whereHas('mascota', fn ($q) => BusquedaTexto::whereLike($q, 'nombre', $term));
        }

        if (! empty($filters['motivo'])) {
            BusquedaTexto::whereLike($query, 'motivo', $filters['motivo']);
        }

        $query->orderBy('creado_en', 'desc');

        if ($paginate) {
            return $query->paginate()->appends($filters);
        }

        return $query->get();
    }

    public function getByFecha(string $fecha, ?array $estados = null)
    {
        $query = $this->model
            ->with(['mascota.propietario', 'mascota.raza.especie', 'veterinario', 'servicio', 'pagos'])
            ->where(function ($q) use ($fecha) {
                $q->whereDate('fecha', $fecha)
                    ->orWhere(function ($q2) use ($fecha) {
                        $q2->whereNull('fecha')->whereDate('creado_en', $fecha);
                    });
            })
            ->orderByRaw('COALESCE(hora, creado_en::time) ASC');

        if ($estados !== null) {
            $query->whereIn('estado', $estados);
        }

        return $query->get();
    }
}
