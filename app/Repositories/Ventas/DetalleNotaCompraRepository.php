<?php

namespace App\Repositories\Ventas;

use App\Models\Ventas\DetalleNotaCompra;

class DetalleNotaCompraRepository
{
    public function getAll()
    {
        return DetalleNotaCompra::orderByDesc('creado_en')->paginate();
    }

    public function findById($id)
    {
        return DetalleNotaCompra::findOrFail($id);
    }

    public function create(array $purchaseNoteDetailData)
    {
        return DetalleNotaCompra::create($purchaseNoteDetailData);
    }

    public function deleteByMedicamentAndPurchaseNoteId($medicamentId, $purchaseNoteId)
    {
        return DetalleNotaCompra::where('producto_id', $medicamentId)
            ->where('nota_compra_id', $purchaseNoteId)
            ->delete();
    }

    public function update($id, array $data)
    {
        return DetalleNotaCompra::where('id', $id)->update($data);
    }

    public function findByPurchaseNoteId($purchaseNoteId)
    {
        return DetalleNotaCompra::where('nota_compra_id', $purchaseNoteId)->with('medicament')->get();
    }

    public function deleteById($id)
    {
        return DetalleNotaCompra::where('id', $id)->delete();
    }
}
