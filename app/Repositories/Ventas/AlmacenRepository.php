<?php

namespace App\Repositories\Ventas;

use App\Models\Ventas\Almacen;

class AlmacenRepository
{
    public function getAll()
    {
        return Almacen::orderByDesc('actualizado_en')->paginate();
    }

    public function getAllWithoutPaginate()
    {
        return Almacen::orderByDesc('actualizado_en')->get();
    }

    public function findById($id)
    {
        return Almacen::findOrFail($id);
    }

    public function create(array $userData)
    {
        return Almacen::create($userData);
    }

    public function update($id, array $data)
    {
        return Almacen::where('id', $id)->update($data);
    }

    public function delete($id)
    {
        return Almacen::where('id', $id)->delete();
    }

    public function search()
    {
        $search = request('search', '');
        if (strlen($search) < 1) {
            return collect();
        }

        return Almacen::where(function ($query) use ($search) {
            $query->where('nombre', 'like', "%{$search}%")
                ->orWhere('ubicacion', 'like', "%{$search}%")
                ->orWhere('descripcion', 'like', "%{$search}%");
        })
            ->take(5)
            ->get();
    }

    public function getGroupedInventories($id)
    {
        return Almacen::with([
            'inventories' => function ($query) {
                $query->selectRaw('producto_id, sum(stock) as total_stock')
                    ->groupBy('producto_id');
            },
        ])->findOrFail($id);
    }
}
