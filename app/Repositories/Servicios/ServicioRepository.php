<?php

namespace App\Repositories\Servicios;

use App\Models\Servicios\Servicio;

class ServicioRepository
{
    public function getAll()
    {
        return Servicio::orderBy('nombre')->paginate(10);
    }

    public function getAllActivos()
    {
        return Servicio::where('esta_activo', true)->orderBy('nombre')->get();
    }

    public function findById(int $id): Servicio
    {
        return Servicio::findOrFail($id);
    }

    public function create(array $data): Servicio
    {
        return Servicio::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return Servicio::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return Servicio::destroy($id) > 0;
    }

    public function search(?string $term)
    {
        $query = Servicio::query();

        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('nombre', 'like', "%{$term}%")
                    ->orWhere('descripcion', 'like', "%{$term}%");
            });
        }

        return $query->orderBy('nombre')->paginate(10);
    }
}
