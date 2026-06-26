<?php

namespace App\Repositories\Ventas;

use App\Models\Ventas\Inventario;

class InventarioRepository
{
    public function getAll()
    {
        return Inventario::orderByDesc('actualizado_en')
            ->with(['medicament', 'warehouse'])
            ->paginate();
    }

    public function findById($id)
    {
        return Inventario::findOrFail($id);
    }

    public function create(array $userData)
    {
        return Inventario::create($userData);
    }

    public function getGroupedInventoriesByMedicament($warehouseId)
    {
        return Inventario::with('medicament')
            ->where('almacen_id', $warehouseId)
            ->selectRaw('producto_id, sum(stock) as total_stock')
            ->groupBy('producto_id')
            ->get();
    }

    public function getInventoriesByWarehoseAndMedicament($warehouseId, $medicamentId)
    {
        return Inventario::where('almacen_id', $warehouseId)
            ->where('producto_id', $medicamentId)
            ->with(['medicament', 'warehouse'])
            ->orderByDesc('creado_en')
            ->paginate();
    }

    public function getTotalStockByMedicamentAndWarehouse($medicamentId, $warehouseId)
    {
        return Inventario::where('almacen_id', $warehouseId)
            ->where('producto_id', $medicamentId)
            ->sum('stock');
    }

    public function update($id, array $data)
    {
        return Inventario::where('id', $id)->update($data);
    }

    public function deleteByPurchaseNoteId($purchaseNoteId)
    {
        return Inventario::whereHas('detalleNotaCompra', fn ($q) => $q->where('nota_compra_id', $purchaseNoteId))->delete();
    }

    public function deleteByMedicamentAndWarehouse($medicamentId, $warehouseId)
    {
        return Inventario::where('producto_id', $medicamentId)
            ->where('almacen_id', $warehouseId)
            ->delete();
    }

    public function findByPurchaseNoteDetailId(int $purchaseNoteDetailId)
    {
        return Inventario::where('detalle_nota_compra_id', $purchaseNoteDetailId)->first();
    }

    public function deleteByPurchaseNoteDetailId(int $purchaseNoteDetailId)
    {
        return Inventario::where('detalle_nota_compra_id', $purchaseNoteDetailId)->delete();
    }

    public function updateInventoryStock($inventoryId, $quantitySold)
    {
        $inventory = Inventario::findOrFail($inventoryId);
        $inventory->stock -= $quantitySold;
        $inventory->save();
    }

    public function restoreInventoryStock($inventoryId, $quantityRestored)
    {
        $inventory = Inventario::findOrFail($inventoryId);
        $inventory->stock += $quantityRestored;
        $inventory->save();
    }

    public function findByMedicamentAndWarehouse(int $warehouseId, int $medicamentId)
    {
        return Inventario::where('almacen_id', $warehouseId)
            ->where('producto_id', $medicamentId);
    }
}
