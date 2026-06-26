<?php

namespace App\Http\Controllers\Ventas;

use App\Http\Controllers\Controller;
use App\Services\Ventas\ProveedorService;
use App\Http\Requests\Ventas\StoreProveedorRequest;
use App\Http\Requests\Ventas\UpdateProveedorRequest;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class ProveedorController extends Controller
{

    public function __construct(protected ProveedorService $ProveedorService) {}

    public function index()
    {
        $suppliers = $this->ProveedorService->getAllSuppliers();
        return Inertia::render('Ventas/Proveedores/Index', compact('suppliers'));
    }

    public function store(StoreProveedorRequest $request)
    {
        // DEBUG: Ver qué datos llegan
        Log::info('Datos recibidos en store:', $request->all());
        Log::info('Datos validados:', $request->validated());

        try {
            $supplier = $this->ProveedorService->createSupplier($request->validated());
            Log::info('Proveedor creado exitosamente:', $supplier->toArray());
            return response()->json($supplier, 201);
        } catch (\Exception $e) {
            Log::error('Error al crear proveedor:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    public function update(UpdateProveedorRequest $request, int $id)
    {
        $this->ProveedorService->updateSupplier($id, $request->validated());
        return response()->json(['message' => 'Proveedor actualizado correctamente']);
    }

    public function destroy(int $id)
    {
        $this->ProveedorService->deleteSupplier($id);
        return response()->json(['message' => 'Proveedor eliminado correctamente']);
    }
}
