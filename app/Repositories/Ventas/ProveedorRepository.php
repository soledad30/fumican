<?php

namespace App\Repositories\Ventas;

use App\Models\Ventas\Proveedor;

class ProveedorRepository
{
    public function getAll()
    {
        return Proveedor::orderByDesc('actualizado_en')
            ->orderByDesc('nombre')
            ->paginate(15);
    }

    public function findById($id)
    {
        return Proveedor::findOrFail($id);
    }

    public function create(array $data)
    {
        return Proveedor::create($data);
    }

    public function update($id, array $data)
    {
        $proveedor = Proveedor::findOrFail($id);
        $proveedor->update($data);

        return $proveedor;
    }

    public function search()
    {
        $search = request('search');
        if (empty($search)) {
            return collect();
        }

        $term = '%'.$search.'%';

        return Proveedor::where(function ($query) use ($term) {
            $query->where('nombre', 'like', $term)
                ->orWhere('pais', 'like', $term)
                ->orWhere('telefono', 'like', $term)
                ->orWhere('email', 'like', $term)
                ->orWhere('direccion', 'like', $term);
        })
            ->orderBy('nombre')
            ->take(5)
            ->get();
    }
}
