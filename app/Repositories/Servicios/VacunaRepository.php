<?php

namespace App\Repositories\Servicios;

use App\Models\Servicios\Vacuna;

class VacunaRepository
{
    public function getAll()
    {
        return Vacuna::orderBy('nombre')->paginate(10);
    }

    public function getAllSinPaginar()
    {
        return Vacuna::orderBy('nombre')->get();
    }

    public function findById(int $id): Vacuna
    {
        return Vacuna::findOrFail($id);
    }

    public function create(array $data): Vacuna
    {
        return Vacuna::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return Vacuna::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return Vacuna::destroy($id) > 0;
    }

    public function search(?string $term)
    {
        $query = Vacuna::query();

        if ($term) {
            $query->where('nombre', 'like', "%{$term}%");
        }

        return $query->orderBy('nombre')->paginate(10);
    }
}
