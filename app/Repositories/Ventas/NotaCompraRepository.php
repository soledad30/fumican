<?php

namespace App\Repositories\Ventas;

use App\Models\Ventas\NotaCompra;

class NotaCompraRepository
{
    public function getAll(array $filters = [], bool $paginate = true)
    {
        $q = NotaCompra::with([
            'almacen:id,nombre,ubicacion,descripcion',
            'proveedor:id,nombre,pais,telefono,email,direccion',
        ])
            ->orderByDesc('creado_en');

        if (! empty($filters['supplier_id'])) {
            $q->where('proveedor_id', $filters['supplier_id']);
        }
        if (! empty($filters['warehouse_id'])) {
            $q->where('almacen_id', $filters['warehouse_id']);
        }
        if (! empty($filters['date_from'])) {
            $q->whereDate('fecha_compra', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $q->whereDate('fecha_compra', '<=', $filters['date_to']);
        }

        if ($paginate) {
            return $q->paginate(15)->appends($filters);
        }

        return $q->get();
    }

    public function findById($id)
    {
        return NotaCompra::with(['almacen', 'proveedor', 'usuario', 'detalles.producto'])->findOrFail($id);
    }

    public function create(array $purchaseNoteData)
    {
        return NotaCompra::create($purchaseNoteData);
    }

    public function update($id, array $data)
    {
        return NotaCompra::where('id', $id)->update($data);
    }

    public function deleteById($id)
    {
        return NotaCompra::where('id', $id)->delete();
    }
}
