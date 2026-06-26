<?php

namespace App\Services\Ventas;

use App\Models\Ventas\Inventario;
use App\Repositories\Ventas\InventarioRepository;

class InventarioService
{
    public function __construct(protected InventarioRepository $inventarioRepository) {}

    public function getAllInventories()
    {
        return $this->inventarioRepository->getAll();
    }

    public function getinventoryById($id)
    {
        return $this->inventarioRepository->findById($id);
    }

    public function createInventory(array $userData)
    {
        return $this->inventarioRepository->create($userData);
    }

    public function getGroupedInventoriesByMedicament($warehouseId)
    {
        return $this->inventarioRepository->getGroupedInventoriesByMedicament($warehouseId);
    }

    public function getInventoriesByWarehoseAndMedicament($warehouseId, $medicamentId)
    {
        return $this->inventarioRepository->getInventoriesByWarehoseAndMedicament($warehouseId, $medicamentId);
    }

    public function getTotalStockByMedicamentAndWarehouse($medicamentId, $warehouseId)
    {
        return $this->inventarioRepository->getTotalStockByMedicamentAndWarehouse($medicamentId, $warehouseId);
    }

    public function updateInventory($id, array $inventoryData)
    {
        return $this->inventarioRepository->update($id, $inventoryData);
    }

    public function deleteInventoriesByPurchaseNoteId($purchaseNoteId)
    {
        return $this->inventarioRepository->deleteByPurchaseNoteId($purchaseNoteId);
    }

    public function deleteInventoriesByMedicamentAndWarehouse($medicamentId, $warehouseId)
    {
        return $this->inventarioRepository->deleteByMedicamentAndWarehouse($medicamentId, $warehouseId);
    }

    public function getInventoryByPurchaseNoteDetailId(int $purchaseNoteDetailId)
    {
        return $this->inventarioRepository->findByPurchaseNoteDetailId($purchaseNoteDetailId);
    }

    public function deleteByPurchaseNoteDetailId(int $purchaseNoteDetailId)
    {
        return $this->inventarioRepository->deleteByPurchaseNoteDetailId($purchaseNoteDetailId);
    }

    public function updateInventoryStock($inventoryId, $quantitySold)
    {
        return $this->inventarioRepository->updateInventoryStock($inventoryId, $quantitySold);
    }

    public function restoreInventoryStock($inventoryId, $quantityRestored)
    {
        return $this->inventarioRepository->restoreInventoryStock($inventoryId, $quantityRestored);
    }

    public function getByMedicamentAndWarehouse(int $medId, int $warehouseId)
    {
        return $this->inventarioRepository
            ->findByMedicamentAndWarehouse($warehouseId, $medId)
            ->first();
    }

    public function adjustStock(int $inventoryId, int $delta)
    {
        $inv = $this->inventarioRepository->findById($inventoryId);
        $inv->stock += $delta;
        $inv->save();
    }

    public function consumeStock(int $warehouseId, int $medicamentId, int $quantity, int $salesDetailId): void
    {
        $remaining = $quantity;
        $lots = Inventario::where('almacen_id', $warehouseId)
            ->where('producto_id', $medicamentId)
            ->where('stock', '>', 0)
            ->orderByRaw('CASE WHEN fecha_vencimiento IS NULL THEN 1 ELSE 0 END')
            ->orderBy('fecha_vencimiento')
            ->orderBy('creado_en')
            ->get();

        foreach ($lots as $lot) {
            if ($remaining <= 0) {
                break;
            }
            $take = min($lot->stock, $remaining);
            $lot->stock -= $take;
            $lot->save();
            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw new \Exception("Stock insuficiente para el producto $medicamentId");
        }
    }

    public function restoreStockForSalesDetail(int $salesDetailId): void
    {
        // Sin tabla movimientos_stock: la restauración se gestiona al editar/eliminar la nota de venta.
    }
}
