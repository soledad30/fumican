<?php

namespace App\Http\Controllers\Ventas;

use App\Enums\UnidadMedidaEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ventas\StoreCategoriaRequest;
use App\Services\Ventas\CategoriaService;
use App\Services\Ventas\ProductoService;
use App\Http\Requests\Ventas\StoreProductoRequest;
use App\Services\Ventas\AlmacenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use PDF;

class ProductoController extends Controller
{
    public function __construct(
        protected ProductoService $ProductoService,
        protected CategoriaService $CategoriaService,
        protected AlmacenService $AlmacenService
    ) {}

    private function datosProductosIndex($medicaments, ?array $filters = null): array
    {
        return [
            'medicaments' => $medicaments,
            'categories' => $this->CategoriaService->getAllCategoriesWithoutPaginate(),
            'categoriesPaginated' => $this->CategoriaService->getAllCategories(),
            'manufacturers' => $this->ProductoService->listarFabricantes(),
            'warehouses' => $this->AlmacenService->getAllWarehousesWithoutPaginate(),
            'unidadesMedida' => UnidadMedidaEnum::labels(),
            'filters' => $filters,
        ];
    }

    public function index(Request $request)
    {
        return Inertia::render(
            'Ventas/Productos/Index',
            $this->datosProductosIndex($this->ProductoService->getAllMedicaments())
        );
    }

    public function search(Request $request)
    {
        $filters = $request->only([
            'name',
            'dosage',
            'manufacturer',
            'expiration_from',
            'expiration_to',
            'controlled_substance',
            'category_id',
            'per_page',
        ]);

        return Inertia::render(
            'Ventas/Productos/Index',
            $this->datosProductosIndex($this->ProductoService->search($filters), $filters)
        );
    }

    public function store(StoreProductoRequest $request): JsonResponse
    {
        $med = $this->ProductoService->createMedicament($request->validated());

        return response()->json([
            'message' => 'Producto creado correctamente.',
            'medicament' => $med->load('categoria'),
        ], 201);
    }

    public function update(StoreProductoRequest $request, int $id): JsonResponse
    {
        $this->ProductoService->updateMedicament($id, $request->validated());

        return response()->json([
            'message' => 'Producto actualizado correctamente.',
        ]);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $this->ProductoService->deleteMedicament($id);

        return response()->json([
            'message' => 'Producto eliminado correctamente.',
        ]);
    }

    public function generatePdf(Request $request)
    {
        $filters = $request->only([
            'name',
            'dosage',
            'manufacturer',
            'expiration_from',
            'expiration_to',
            'controlled_substance',
            'category_id',
        ]);

        $medicaments = $this->ProductoService->getFilteredMedicaments($filters);

        $pdf = PDF::loadView('pdf.medicaments_report', compact('medicaments', 'filters'))
            ->setPaper('a4', 'landscape');

        return $pdf->stream('reporte_productos_'.now()->format('Ymd').'.pdf');
    }
}
