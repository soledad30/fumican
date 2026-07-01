<?php

namespace App\Repositories\Usuarios;

use App\Models\Usuarios\Rol;
use App\Support\NormalizaNombre;
use Illuminate\Support\Collection;

class RolRepository
{
    public function __construct(protected Rol $model) {}

    public function getAll()
    {
        $query = $this->model->query()
            ->when(request()->boolean('with_permissions'), function ($query) {
                $query->with('permisos:id,nombre');
            })
            ->orderBy('actualizado_en', 'asc');

        if (request()->has('page')) {
            return $query->paginate();
        }

        return $query->get();
    }

    public function getAllPaginated()
    {
        return $this->model->query()
            ->with('permisos:id,nombre')
            ->when(request('search_term'), function ($query, $term) {
                $query->where('nombre', 'like', '%'.$term.'%');
            })
            ->orderBy('actualizado_en', 'asc')
            ->paginate();
    }

    public function listarParaValidacion(): Collection
    {
        return $this->model->query()->orderBy('nombre')->get(['id', 'nombre']);
    }

    public function buscarDuplicadoPorNombre(string $nombre, ?int $exceptId = null): ?Rol
    {
        $normalizado = NormalizaNombre::rol($nombre);

        if ($normalizado === '') {
            return null;
        }

        return $this->model->query()
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->get()
            ->first(fn (Rol $rol) => NormalizaNombre::rol($rol->nombre) === $normalizado);
    }

    public function buscarSimilares(string $nombre, ?int $exceptId = null): Collection
    {
        $normalizado = NormalizaNombre::rol($nombre);

        if (mb_strlen($normalizado) < 2) {
            return collect();
        }

        return $this->model->query()
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->get()
            ->filter(function (Rol $rol) use ($normalizado) {
                $otro = NormalizaNombre::rol($rol->nombre);

                if ($otro === $normalizado) {
                    return false;
                }

                return str_contains($otro, $normalizado) || str_contains($normalizado, $otro);
            })
            ->values();
    }

    public function getById($id)
    {
        return $this->model->findOrFail($id);
    }

    public function findByName($name)
    {
        return $this->model->where('nombre', $name)->first();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $role = $this->model->findOrFail($id);

        return $role->update($data);
    }

    public function delete($id): bool
    {
        return (bool) $this->model->where('id', $id)->delete();
    }
}
