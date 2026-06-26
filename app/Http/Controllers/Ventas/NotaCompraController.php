<?php

namespace App\Http\Controllers\Ventas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ventas\StoreNotaCompraRequest;
use App\Http\Requests\Ventas\UpdateNotaCompraRequest;
use App\Services\Ventas\InventarioService;
use App\Services\Ventas\ProductoService;
use App\Support\PrecioFarmacia;
use App\Services\Ventas\DetalleNotaCompraService;
use App\Services\Ventas\NotaCompraService;
use App\Services\Ventas\ProveedorService;
use App\Services\Ventas\AlmacenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use PDF;

class NotaCompraController extends Controller
{
    public function __construct(
        protected NotaCompraService $NotaCompraService,
        protected DetalleNotaCompraService $DetalleNotaCompraService,
        protected InventarioService $InventarioService,
        protected ProveedorService $ProveedorService,
        protected AlmacenService $AlmacenService,
        protected ProductoService $ProductoService
    ) {}

    public function index(Request $request)
    {
        // Sin filtros: solo muestra todo paginado
        $purchases = $this->NotaCompraService->getAllPurchaseNotes();
        $suppliers = $this->ProveedorService->getAllSuppliers()->items();
        $warehouses = $this->AlmacenService->getAllWarehouses()->items();

        return Inertia::render('Ventas/NotasCompra/Index', [
            'purchases'  => $purchases,
            'suppliers'  => $suppliers,
            'warehouses' => $warehouses,
            'filters'    => null,
        ]);
    }

    public function search(Request $request)
    {
        $filters = $request->only([
            'supplier_id',
            'warehouse_id',
            'date_from',
            'date_to',
            'per_page',
        ]);
        $purchases = $this->NotaCompraService->getFilteredPurchaseNotes($filters);

        $suppliers = $this->ProveedorService->getAllSuppliers()->items();
        $warehouses = $this->AlmacenService->getAllWarehouses()->items();

        return Inertia::render('Ventas/NotasCompra/Index', [
            'purchases'  => $purchases,
            'suppliers'  => $suppliers,
            'warehouses' => $warehouses,
            'filters'    => $filters,
        ]);
    }

    public function create(ProveedorService $ProveedorService, AlmacenService $AlmacenService, ProductoService $ProductoService)
    {
        $suppliers = $ProveedorService->getAllSuppliers()->items();
        $warehouses = $AlmacenService->getAllWarehouses()->items();
        $medicamentsList = $ProductoService->getAllMedicaments()->items();

        return Inertia::render('Ventas/NotasCompra/Form', [
            'formAction' => 'create',
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'medicamentsList' => $medicamentsList,
            'pricingConfig' => $this->configPrecios(),
        ]);
    }

    public function store(
        StoreNotaCompraRequest $request,
        InventarioService $InventarioService,
        DetalleNotaCompraService $DetalleNotaCompraService
    ) {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $purchaseNoteData = [
                'almacen_id' => $data['warehouse_id'],
                'proveedor_id' => $data['supplier_id'],
                'usuario_id' => Auth::id(),
                'fecha_compra' => now()->toDateString(),
                'monto_total' => $data['total_amount'],
            ];

            // 1) Crear la nota de compra
            $purchaseNote = $this->NotaCompraService->createPurchaseNote($purchaseNoteData);

            // 2) Recorrer cada medicamento y crear detalle + asignar inventario
            foreach ($data['medicaments'] as $medInput) {
                [$precioCompra, $precioVenta] = $this->preciosLineaCompra($medInput);

                $purchaseNoteDetailData = [
                    'cantidad' => $medInput['quantity'],
                    'precio_compra' => $precioCompra,
                    'subtotal' => $medInput['subtotal'],
                    'nota_compra_id' => $purchaseNote->id,
                    'producto_id' => $medInput['id'],
                ];
                $detail = $DetalleNotaCompraService->createPurchaseNoteDetail($purchaseNoteDetailData);

                $inventoryData = [
                    'stock' => $medInput['quantity'],
                    'precio_compra' => $precioCompra,
                    'precio' => $precioVenta,
                    'fecha_vencimiento' => $medInput['expiration_date'] ?? null,
                    'almacen_id' => $data['warehouse_id'],
                    'producto_id' => $medInput['id'],
                    'detalle_nota_compra_id' => $detail->id,
                ];
                $InventarioService->createInventory($inventoryData);

                $this->sincronizarProductoTrasCompra($medInput, $precioVenta);
            }

            DB::commit();
            return response()->json([
                'message' => 'Nota de compra creada exitosamente',
                'purchaseNote' => $purchaseNote, // opcional
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear la nota de compra',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        $purchaseNote = $this->NotaCompraService->getPurchaseNoteById($id);
        $purchaseNoteDetails = $this->DetalleNotaCompraService
            ->getPurchaseNoteDetailsByPurchaseNoteId($id);

        // Siempre respondemos JSON:
        return response()->json([
            'purchaseNote'        => $purchaseNote,
            'purchaseNoteDetails' => $purchaseNoteDetails,
        ]);
    }

    public function generatePdf($id)
    {
        $purchaseNote = $this->NotaCompraService->getPurchaseNoteById($id);
        $purchaseNoteDetails = $this->DetalleNotaCompraService->getPurchaseNoteDetailsByPurchaseNoteId($id);

        $pdf = PDF::loadView('pdf.purchase_note', compact('purchaseNote', 'purchaseNoteDetails'));
        return $pdf->stream('nota_de_compra.pdf');
    }

    public function edit(int $purchaseNoteId, ProveedorService $ProveedorService, AlmacenService $AlmacenService, ProductoService $ProductoService)
    {
        $purchaseNote = $this->NotaCompraService->getPurchaseNoteById($purchaseNoteId);
        $purchaseNoteDetails = $this->DetalleNotaCompraService->getPurchaseNoteDetailsByPurchaseNoteId($purchaseNoteId);
        $suppliers = $ProveedorService->getAllSuppliers()->items();
        $warehouses = $AlmacenService->getAllWarehouses()->items();
        $medicamentsList = $ProductoService->getAllMedicaments()->items();

        return Inertia::render('Ventas/NotasCompra/FormEdit', [
            'purchaseNote' => $purchaseNote,
            'purchaseNoteDetails' => $purchaseNoteDetails,
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'medicamentsList' => $medicamentsList,
            'pricingConfig' => $this->configPrecios(),
        ]);
    }

    public function update(
        UpdateNotaCompraRequest $request,
        $id,
        InventarioService $InventarioService,
        DetalleNotaCompraService $DetalleNotaCompraService
    ) {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            // 1) Obtener la nota de compra original
            $originalPurchaseNote = $this->NotaCompraService->getPurchaseNoteById($id);
            $originalWarehouseId = $originalPurchaseNote->warehouse_id;

            // 2) Actualizar datos generales de la nota de compra
            $purchaseNoteData = [
                'almacen_id' => $data['warehouse_id'],
                'proveedor_id' => $data['supplier_id'],
                'monto_total' => $data['total_amount'],
            ];
            $this->NotaCompraService->updatePurchaseNote($id, $purchaseNoteData);

            // 3) Obtener los detalles existentes y mapearlos por ID
            $existingDetails = $DetalleNotaCompraService->getPurchaseNoteDetailsByPurchaseNoteId($id);
            // $existingDetails es una colección de DetalleNotaCompra (con Producto incluido si definimos with('medicament'))
            $existingDetailsMap = $existingDetails->keyBy('id')->toArray();
            $existingDetailIds = array_keys($existingDetailsMap);

            // 4) Extraer los IDs que vienen del front (los medInput['id'] no son IDs de detalle,
            //    sino IDs de medicamento. Debemos asumir que, en el arreglo de front, hay un campo detail_id si es edición.)
            //    Por ejemplo, recomendamos que en el front capturemos para cada Producto algo como:
            //      { detail_id: 123, id: 5, quantity: 10, purchase_price: 20, subtotal: 200 }
            //    Si no tienen 'detail_id', entonces debemos distinguir existencia por posición u otro criterio.
            //
            //    Aquí asumiremos que el front envía un campo `detail_id` cuando es un detalle existente.
            //    De no ser así, habría que buscar de otra forma (p.ej. comparar medicament_id y demás).

            $sentDetailsRaw = $data['medicaments'];
            // Extraemos los detail_id que vienen del front:
            $sentDetailIds = array_filter(array_map(function ($d) {
                return $d['detail_id'] ?? null;
            }, $sentDetailsRaw));

            // 5) DELETED: detectar detalles que ya no existen en $sentDetailIds y borrar sus inventarios y detalles
            $detailsToDelete = array_diff($existingDetailIds, $sentDetailIds);
            foreach ($detailsToDelete as $detailId) {
                // 5.1) Borrar inventario correspondiente a ese detalle
                $InventarioService->deleteByPurchaseNoteDetailId($detailId);

                // 5.2) Borrar el detalle de la nota
                $DetalleNotaCompraService->deleteById($detailId);
            }

            // 6) Procesar cada detalle enviado desde el front
            foreach ($sentDetailsRaw as $medInput) {
                // Determinar si es EXISTENTE o NUEVO:
                if (isset($medInput['detail_id']) && in_array($medInput['detail_id'], $existingDetailIds)) {
                    $detailId = $medInput['detail_id'];
                    [$precioCompra, $precioVenta] = $this->preciosLineaCompra($medInput);

                    $purchaseNoteDetailData = [
                        'cantidad' => $medInput['quantity'],
                        'precio_compra' => $precioCompra,
                        'subtotal' => $medInput['subtotal'],
                        'nota_compra_id' => $id,
                        'producto_id' => $medInput['id'],
                    ];
                    $DetalleNotaCompraService->updatePurchaseNoteDetail($detailId, $purchaseNoteDetailData);

                    $existingInventory = $InventarioService->getInventoryByPurchaseNoteDetailId($detailId);

                    $inventoryData = [
                        'stock' => $medInput['quantity'],
                        'precio_compra' => $precioCompra,
                        'precio' => $precioVenta,
                        'fecha_vencimiento' => $medInput['expiration_date'] ?? null,
                        'almacen_id' => $data['warehouse_id'],
                        'producto_id' => $medInput['id'],
                        'detalle_nota_compra_id' => $detailId,
                    ];

                    if ($existingInventory) {
                        // Actualizar usando su ID
                        $InventarioService->updateInventory($existingInventory->id, $inventoryData);
                    } else {
                        $InventarioService->createInventory($inventoryData);
                    }
                    $this->sincronizarProductoTrasCompra($medInput, $precioVenta);
                } else {
                    [$precioCompra, $precioVenta] = $this->preciosLineaCompra($medInput);

                    $purchaseNoteDetailData = [
                        'cantidad' => $medInput['quantity'],
                        'precio_compra' => $precioCompra,
                        'subtotal' => $medInput['subtotal'],
                        'nota_compra_id' => $id,
                        'producto_id' => $medInput['id'],
                    ];
                    $newDetail = $DetalleNotaCompraService->createPurchaseNoteDetail($purchaseNoteDetailData);

                    $inventoryData = [
                        'stock' => $medInput['quantity'],
                        'precio_compra' => $precioCompra,
                        'precio' => $precioVenta,
                        'fecha_vencimiento' => $medInput['expiration_date'] ?? null,
                        'almacen_id' => $data['warehouse_id'],
                        'producto_id' => $medInput['id'],
                        'detalle_nota_compra_id' => $newDetail->id,
                    ];
                    $InventarioService->createInventory($inventoryData);
                    $this->sincronizarProductoTrasCompra($medInput, $precioVenta);
                }
            }

            DB::commit();
            return response()->json([
                'message' => 'Nota de compra actualizada exitosamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar la nota de compra',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            // 1) Obtener todos los detalles de la nota de compra
            $purchaseNoteDetails = $this->DetalleNotaCompraService
                ->getPurchaseNoteDetailsByPurchaseNoteId($id);

            // 2) Para cada detail, borrar primero su inventario y luego el detail
            foreach ($purchaseNoteDetails as $detail) {
                // Borra el inventario asociado a este detail
                $this->InventarioService->deleteByPurchaseNoteDetailId($detail->id);

                // Borra el PurchaseNoteDetail
                $this->DetalleNotaCompraService->deleteById($detail->id);
            }

            // 3) Finalmente, borrar la propia nota de compra
            $this->NotaCompraService->deletePurchaseNoteById($id);

            DB::commit();
            return response()->json([
                'message' => "Nota de compra #{$id} eliminada correctamente",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar la nota de compra',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function report(Request $request)
    {
        $filters = $request->only([
            'supplier_id',
            'warehouse_id',
            'date_from',
            'date_to',
        ]);

        // Trae todos los registros filtrados (sin paginar)
        $purchases = $this->NotaCompraService->getFilteredPurchaseNotes($filters, $paginate = false);

        $pdf = PDF::loadView('pdf.purchases_report', compact('purchases', 'filters'))
            ->setPaper('a4', 'landscape');

        return $pdf->stream('reporte_notas_compra_' . now()->format('Ymd') . '.pdf');
    }

    private function configPrecios(): array
    {
        return [
            'iva_porcentaje' => config('ventas.iva_porcentaje'),
            'margen_default' => config('ventas.margen_default'),
        ];
    }

    private function preciosLineaCompra(array $medInput): array
    {
        $precioCompra = (float) $medInput['purchase_price'];
        $precioVenta = ! empty($medInput['sale_price'])
            ? (float) $medInput['sale_price']
            : PrecioFarmacia::calcularVenta($precioCompra)['precio_venta'];

        return [$precioCompra, $precioVenta];
    }

    private function sincronizarProductoTrasCompra(array $medInput, float $precioVenta): void
    {
        $this->ProductoService->actualizarReferenciaPostCompra(
            (int) $medInput['id'],
            $precioVenta,
            $medInput['expiration_date'] ?? null
        );
    }
}
