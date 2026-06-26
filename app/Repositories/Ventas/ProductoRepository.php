<?php

namespace App\Repositories\Ventas;

use App\Models\Ventas\Categoria;
use App\Models\Ventas\Producto;

class ProductoRepository
{
    private function aplicarFiltroCategoria($query, $categoryId): void
    {
        if (empty($categoryId)) {
            return;
        }

        $categoria = Categoria::find($categoryId);
        if (! $categoria) {
            $query->where('categoria_id', $categoryId);

            return;
        }

        $ids = Categoria::query()
            ->where('nombre', $categoria->nombre)
            ->pluck('id');

        $query->whereIn('categoria_id', $ids);
    }

    public function fabricantesDistintos(): array
    {
        return Producto::query()
            ->whereNotNull('fabricante')
            ->where('fabricante', '!=', '')
            ->distinct()
            ->orderBy('fabricante')
            ->pluck('fabricante')
            ->all();
    }
    public function getAll()
    {
        return $this->queryConStock()
            ->orderByDesc('actualizado_en')
            ->paginate(15);
    }

    /** Listado completo para selects en notas de venta/compra. */
    public function listForSelect()
    {
        return Producto::query()
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'precio_venta_referencia']);
    }

    public function findById($id)
    {
        return Producto::findOrFail($id);
    }

    public function create(array $userData)
    {
        return Producto::create($userData);
    }

    public function update($id, array $data)
    {
        return Producto::where('id', $id)->update($data);
    }

    public function search(array $filters)
    {
        $query = $this->queryConStock();

        if (! empty($filters['name'])) {
            $query->where('nombre', 'like', "%{$filters['name']}%");
        }
        if (! empty($filters['dosage'])) {
            $query->where('dosificacion', 'like', "%{$filters['dosage']}%");
        }
        if (! empty($filters['manufacturer'])) {
            $query->where('fabricante', $filters['manufacturer']);
        }
        if (! empty($filters['expiration_from'])) {
            $query->whereDate('fecha_vencimiento', '>=', $filters['expiration_from']);
        }
        if (! empty($filters['expiration_to'])) {
            $query->whereDate('fecha_vencimiento', '<=', $filters['expiration_to']);
        }
        if (isset($filters['controlled_substance']) && $filters['controlled_substance'] !== '') {
            $query->where('sustancia_controlada', $filters['controlled_substance'] === 'yes');
        }
        if (! empty($filters['category_id'])) {
            $this->aplicarFiltroCategoria($query, $filters['category_id']);
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query
            ->orderByDesc('actualizado_en')
            ->paginate($perPage)
            ->appends($filters);
    }

    public function delete($id)
    {
        return Producto::destroy($id);
    }

    public function filtered(array $filters)
    {
        $query = $this->queryConStock();

        if (! empty($filters['name'])) {
            $query->where('nombre', 'like', "%{$filters['name']}%");
        }
        if (! empty($filters['dosage'])) {
            $query->where('dosificacion', 'like', "%{$filters['dosage']}%");
        }
        if (! empty($filters['manufacturer'])) {
            $query->where('fabricante', $filters['manufacturer']);
        }
        if (! empty($filters['expiration_from'])) {
            $query->whereDate('fecha_vencimiento', '>=', $filters['expiration_from']);
        }
        if (! empty($filters['expiration_to'])) {
            $query->whereDate('fecha_vencimiento', '<=', $filters['expiration_to']);
        }
        if (isset($filters['controlled_substance']) && $filters['controlled_substance'] !== '') {
            $query->where('sustancia_controlada', $filters['controlled_substance'] === 'yes');
        }
        if (! empty($filters['category_id'])) {
            $this->aplicarFiltroCategoria($query, $filters['category_id']);
        }

        return $query->orderByDesc('actualizado_en')->get();
    }

    private function queryConStock()
    {
        return Producto::query()
            ->with('categoria')
            ->withSum('inventarios as stock_total', 'stock');
    }
}
