<?php

namespace App\Repositories\Servicios;

use App\Models\Servicios\Veterinario;

class VeterinarioRepository
{
    public function __construct(protected Veterinario $model) {}

    public function getAllPaginated(int $perPage = 15)
    {
        return $this->model
            ->with('usuario:id,nombre,email')
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->paginate($perPage);
    }

    public function search(array $filters)
    {
        $query = $this->model->with('usuario:id,nombre,email');

        if (! empty($filters['search_term'])) {
            $term = $filters['search_term'];
            $query->where(function ($q) use ($term) {
                $q->where('nombre', 'like', "%{$term}%")
                    ->orWhere('apellido', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('ci', 'like', "%{$term}%")
                    ->orWhere('especialidad', 'like', "%{$term}%");
            });
        }

        return $query
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->paginate(15)
            ->appends($filters);
    }

    public function findById(int $id): Veterinario
    {
        return $this->model->with('usuario')->findOrFail($id);
    }

    public function create(array $data): Veterinario
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return (bool) $this->model->where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->destroy($id);
    }

    public function sinUsuario()
    {
        return $this->model
            ->whereNull('usuario_id')
            ->where('esta_activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'apellido', 'email', 'es_especialista', 'especialidad']);
    }

    public function especialidadesDistintas(): array
    {
        return $this->model
            ->whereNotNull('especialidad')
            ->where('especialidad', '!=', '')
            ->distinct()
            ->orderBy('especialidad')
            ->pluck('especialidad')
            ->all();
    }
}
