<?php

namespace App\Repositories\Servicios;

use App\Models\Servicios\HistorialVacunacion;

class HistorialVacunacionRepository
{
    public function getAll()
    {
        return HistorialVacunacion::with(['mascota.propietario', 'vacuna', 'veterinario'])
            ->orderByDesc('fecha_aplicacion')
            ->paginate(10);
    }

    public function findById(int $id): HistorialVacunacion
    {
        return HistorialVacunacion::with(['mascota', 'vacuna', 'veterinario'])->findOrFail($id);
    }

    public function create(array $data): HistorialVacunacion
    {
        return HistorialVacunacion::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return HistorialVacunacion::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return HistorialVacunacion::destroy($id) > 0;
    }

    public function search(?string $term)
    {
        $query = HistorialVacunacion::with(['mascota', 'vacuna', 'veterinario']);

        if ($term) {
            $query->whereHas('mascota', fn ($q) => $q->where('nombre', 'like', "%{$term}%"));
        }

        return $query->orderByDesc('fecha_aplicacion')->paginate(10);
    }
}
