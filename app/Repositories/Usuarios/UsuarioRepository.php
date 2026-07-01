<?php

namespace App\Repositories\Usuarios;

use App\Models\Usuario;

class UsuarioRepository
{
    public function __construct(protected Usuario $model) {}

    public function getAllPaginated()
    {
        return $this->enriquecerPaginado(
            $this->model
                ->with(['rol:id,nombre', 'clientePerfil', 'veterinarioPerfil'])
                ->orderBy('actualizado_en', 'desc')
                ->paginate()
        );
    }

    public function search(array $filters)
    {
        $query = $this->model->with(['rol:id,nombre', 'clientePerfil', 'veterinarioPerfil']);

        if (! empty($filters['search_term'])) {
            $term = $filters['search_term'];
            $query->where(function ($q) use ($term) {
                $q->where('nombre', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhereHas('clientePerfil', function ($cq) use ($term) {
                        $cq->where('telefono', 'like', "%{$term}%")
                            ->orWhere('ci', 'like', "%{$term}%");
                    });
            });
        }

        if (! empty($filters['role_id'])) {
            $query->where('rol_id', $filters['role_id']);
        }

        return $this->enriquecerPaginado(
            $query->orderBy('actualizado_en', 'desc')->paginate()->appends($filters)
        );
    }

    protected function enriquecerPaginado($paginator)
    {
        return $paginator->through(function (Usuario $user) {
            $perfil = $user->clientePerfil ?? $user->veterinarioPerfil;
            $user->setAttribute('telefono', $user->clientePerfil?->telefono ?? $user->veterinarioPerfil?->telefono);
            $user->setAttribute('ci', $user->clientePerfil?->ci);
            $user->setAttribute('fecha_nacimiento', $user->clientePerfil?->fecha_nacimiento);
            $user->setAttribute('direccion', $user->clientePerfil?->direccion);
            $user->setAttribute('perfil_tipo', $user->clientePerfil ? 'cliente' : ($user->veterinarioPerfil ? 'veterinario' : null));
            $user->setAttribute('especialidad', $user->veterinarioPerfil?->especialidad);

            return $user;
        });
    }

    public function getById($id)
    {
        return $this->model
            ->with(['rol:id,nombre', 'clientePerfil', 'veterinarioPerfil'])
            ->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $user = $this->model->findOrFail($id);

        return $user->update($data);
    }

    public function delete($id)
    {
        return $this->model->destroy($id);
    }
}
