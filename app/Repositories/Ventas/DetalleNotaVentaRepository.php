<?php

namespace App\Repositories\Ventas;

use App\Models\Ventas\DetalleNotaVenta;

class DetalleNotaVentaRepository
{
    public function getAll()
    {
        return DetalleNotaVenta::orderByDesc('creado_en')->paginate();
    }

    public function findById($id)
    {
        return DetalleNotaVenta::findOrFail($id);
    }

    public function create(array $salesNoteDetailData)
    {
        return DetalleNotaVenta::create($salesNoteDetailData);
    }

    public function update($id, array $data)
    {
        return DetalleNotaVenta::where('id', $id)->update($data);
    }

    public function findBySalesNoteId($salesNoteId)
    {
        return DetalleNotaVenta::where('nota_venta_id', $salesNoteId)->with('medicament')->get();
    }
}
