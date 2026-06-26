<?php

namespace App\Http\Controllers\Ventas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ventas\StoreInventarioRequest;
use App\Services\Ventas\AlmacenService;
use App\Services\Ventas\InventarioService;
use App\Services\Ventas\ProductoService;
use App\Http\Requests\Ventas\StoreAlmacenRequest;
use App\Http\Requests\Ventas\UpdateInventarioRequest;
use App\Models\Ventas\Inventario;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;

class AlmacenController extends Controller
{
    public function __construct(protected AlmacenService $AlmacenService) {}

    public function index()
    {
        $warehouses = $this->AlmacenService->getAllWarehouses();
        return Inertia::render('Ventas/Almacenes/Index', [
            'warehouses' => $warehouses,
        ]);
    }

    public function store(StoreAlmacenRequest $request): JsonResponse
    {
        $warehouse = $this->AlmacenService->createWarehouse($request->validated());

        return response()->json([
            'message'   => "Almacén «{$warehouse->name}» creado correctamente",
            'warehouse' => $warehouse,
        ], 201);
    }


    public function show(int $warehouseId, InventarioService $InventarioService)
    {
        $inventories = $InventarioService->getGroupedInventoriesByMedicament($warehouseId);
        $warehouse = $this->AlmacenService->getWarehouseById($warehouseId);
        return Inertia::render('Ventas/Almacenes/Show', [
            'warehouse' => $warehouse,
            'inventories' => $inventories
        ]);
    }

    // Actualizar almacén
    public function update(StoreAlmacenRequest $request, int $id): JsonResponse
    {
        // Esto devuelve un int…
        $rows = $this->AlmacenService->updateWarehouse($id, $request->validated());
        // …así que vuelves a buscar el modelo
        $warehouse = $this->AlmacenService->getWarehouseById($id);

        return response()->json([
            'message'   => "Almacén «{$warehouse->name}» actualizado correctamente",
            'warehouse' => $warehouse,
        ]);
    }


    // Eliminar almacén (usamos POST para facilitar integración con Inertia y evitar problemas con DELETE)
    public function destroy(int $id): JsonResponse
    {
        // Primero obtenemos el modelo para extraer el nombre
        $warehouse = $this->AlmacenService->getWarehouseById($id);
        $name      = $warehouse->name;

        // Luego lo borramos
        $this->AlmacenService->deleteWarehouse($id);

        return response()->json([
            'message' => "Almacén «{$name}» eliminado correctamente",
        ]);
    }


    public function showInventoryMedicament(int $warehouseId, int $medicamentId, InventarioService $InventarioService, ProductoService $ProductoService)
    {
        $inventories = $InventarioService->getInventoriesByWarehoseAndMedicament($warehouseId, $medicamentId);
        $warehouse = $this->AlmacenService->getWarehouseById($warehouseId);
        $medicament = $ProductoService->getMedicamentById($medicamentId);
        return Inertia::render('Ventas/Almacenes/ShowInventoryMedicament', [
            'warehouse' => $warehouse,
            'inventories' => $inventories,
            'medicament' => $medicament
        ]);
    }

    public function storeInventory(StoreInventarioRequest $request, $warehouseId, $medicamentId)
    {
        $data = $request->validated();
        $data['almacen_id'] = $warehouseId;
        $data['producto_id'] = $medicamentId;
        $data['detalle_nota_compra_id'] = null;

        Inventario::create($data);

        return back()->with('success', 'Lote agregado exitosamente');
    }

    public function updateInventory(
        UpdateInventarioRequest $request,
        int $warehouseId,
        int $medicamentId,
        int $inventoryId
    ) {
        $data = $request->validated();

        $inventory = Inventario::where('almacen_id', $warehouseId)
            ->where('producto_id', $medicamentId)
            ->findOrFail($inventoryId);

        $inventory->update($data);

        return back()->with('success', 'Lote actualizado correctamente');
    }

    public function destroyInventory(
        int $warehouseId,
        int $medicamentId,
        int $inventoryId
    ) {
        $inv = Inventario::where('almacen_id', $warehouseId)
            ->where('producto_id', $medicamentId)
            ->findOrFail($inventoryId);

        $inv->delete();

        return back()->with('success', 'Lote eliminado correctamente');
    }
}
