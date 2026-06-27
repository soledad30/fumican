<?php

namespace App\Services\Ventas;

use App\Models\Ventas\Inventario;
use App\Models\Ventas\MovimientoInventario;
use App\Repositories\Ventas\InventarioRepository;
use Illuminate\Support\Facades\Auth;

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
        $inventory = $this->inventarioRepository->create($userData);

        $this->registrarMovimiento([
            'producto_id' => $inventory->producto_id,
            'almacen_id' => $inventory->almacen_id,
            'inventario_id' => $inventory->id,
            'detalle_nota_compra_id' => $inventory->detalle_nota_compra_id,
            'tipo' => 'entrada',
            'cantidad' => (int) $inventory->stock,
            'stock_anterior' => 0,
            'stock_posterior' => (int) $inventory->stock,
            'notas' => 'Ingreso por nota de compra',
        ]);

        return $inventory;
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
        $inv = $this->inventarioRepository->findById($inventoryId);
        $stockAnterior = (int) $inv->stock;
        $result = $this->inventarioRepository->restoreInventoryStock($inventoryId, $quantityRestored);
        $inv->refresh();

        $this->registrarMovimiento([
            'producto_id' => $inv->producto_id,
            'almacen_id' => $inv->almacen_id,
            'inventario_id' => $inv->id,
            'tipo' => 'entrada',
            'cantidad' => (int) $quantityRestored,
            'stock_anterior' => $stockAnterior,
            'stock_posterior' => (int) $inv->stock,
            'notas' => 'Restauración de stock',
        ]);

        return $result;
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
        $stockAnterior = (int) $inv->stock;
        $inv->stock += $delta;
        $inv->save();

        $this->registrarMovimiento([
            'producto_id' => $inv->producto_id,
            'almacen_id' => $inv->almacen_id,
            'inventario_id' => $inv->id,
            'tipo' => 'ajuste',
            'cantidad' => abs($delta),
            'stock_anterior' => $stockAnterior,
            'stock_posterior' => (int) $inv->stock,
            'notas' => $delta >= 0 ? 'Ajuste positivo' : 'Ajuste negativo',
        ]);
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
            $stockAnterior = (int) $lot->stock;
            $take = min($lot->stock, $remaining);
            $lot->stock -= $take;
            $lot->save();
            $remaining -= $take;

            $this->registrarMovimiento([
                'producto_id' => $medicamentId,
                'almacen_id' => $warehouseId,
                'inventario_id' => $lot->id,
                'detalle_nota_venta_id' => $salesDetailId,
                'tipo' => 'salida',
                'cantidad' => $take,
                'stock_anterior' => $stockAnterior,
                'stock_posterior' => (int) $lot->stock,
                'notas' => 'Salida por venta',
            ]);
        }

        if ($remaining > 0) {
            throw new \Exception("Stock insuficiente para el producto $medicamentId");
        }
    }

    public function restoreStockForSalesDetail(int $salesDetailId): void
    {
        $movimientos = MovimientoInventario::query()
            ->where('detalle_nota_venta_id', $salesDetailId)
            ->where('tipo', 'salida')
            ->get();

        foreach ($movimientos as $mov) {
            if (! $mov->inventario_id) {
                continue;
            }

            $inv = Inventario::find($mov->inventario_id);
            if (! $inv) {
                continue;
            }

            $stockAnterior = (int) $inv->stock;
            $inv->stock += $mov->cantidad;
            $inv->save();

            $this->registrarMovimiento([
                'producto_id' => $inv->producto_id,
                'almacen_id' => $inv->almacen_id,
                'inventario_id' => $inv->id,
                'detalle_nota_venta_id' => $salesDetailId,
                'tipo' => 'entrada',
                'cantidad' => (int) $mov->cantidad,
                'stock_anterior' => $stockAnterior,
                'stock_posterior' => (int) $inv->stock,
                'notas' => 'Reversión por edición/eliminación de venta',
            ]);
        }
    }

    private function registrarMovimiento(array $data): void
    {
        MovimientoInventario::create(array_merge($data, [
            'usuario_id' => Auth::id(),
            'creado_en' => now(),
        ]));
    }
}
