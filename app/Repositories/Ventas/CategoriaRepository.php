<?php

namespace App\Repositories\Ventas;

use App\Models\Ventas\Categoria;

class CategoriaRepository
{
    public function getAll()
    {
        return Categoria::orderByDesc('actualizado_en')->orderByDesc('nombre')->paginate(15);
    }

    public function getAllWithoutPaginate()
    {
        return Categoria::orderBy('nombre')
            ->get()
            ->unique(fn (Categoria $c) => mb_strtolower(trim($c->nombre ?? '')))
            ->values();
    }

    public function findById($id)
    {
        return Categoria::findOrFail($id);
    }

    public function create(array $data)
    {
        return Categoria::create($data);
    }

    public function update($id, array $data)
    {
        return Categoria::where('id', $id)->update($data);
    }

    public function delete($id)
    {
        return Categoria::destroy($id) > 0;
    }

    public function search(?string $term = null)
    {
        $query = Categoria::query();

        if ($term) {
            $query->where('nombre', 'like', '%'.$term.'%');
        }

        return $query->orderBy('nombre')->paginate(15);
    }
}

