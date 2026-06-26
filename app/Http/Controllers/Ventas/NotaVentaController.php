<?php

namespace App\Http\Controllers\Ventas;

use App\Http\Controllers\Controller;
use App\Services\Ventas\DetalleNotaVentaService;
use App\Services\Ventas\NotaVentaService;
use App\Services\Ventas\AlmacenService;
use App\Services\Ventas\ProductoService;
use App\Services\Ventas\InventarioService;
use App\Services\Servicios\ClienteService;
use App\Http\Requests\Ventas\StoreNotaVentaRequest;
use App\Http\Requests\Ventas\UpdateNotaVentaRequest;
use App\Models\Ventas\NotaVenta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Http\Request;
use PDF;

class NotaVentaController extends Controller
{
    public function __construct(protected NotaVentaService $NotaVentaService, protected InventarioService $InventarioService, protected AlmacenService $AlmacenService, protected ClienteService $ClienteService) {}

    public function index()
    {
        $sales = $this->NotaVentaService->getAllSalesNote();
        $customers = $this->ClienteService->getAllCustomers()->items();
        $warehouses = $this->AlmacenService->getAllWarehouses()->items();

        return Inertia::render('Ventas/NotasVenta/Index', [
            'sales'      => $sales,
            'customers'  => $customers,
            'warehouses' => $warehouses,
            'filters'    => null,
        ]);
    }

    public function search(Request $request)
    {
        $filters = $request->only([
            'customer_id',
            'warehouse_id',
            'date_from',
            'date_to',
            'per_page',
        ]);
        $sales = $this->NotaVentaService->getFilteredSalesNotes($filters);

        $customers = $this->ClienteService->getAllCustomers()->items();
        $warehouses = $this->AlmacenService->getAllWarehouses()->items();

        return Inertia::render('Ventas/NotasVenta/Index', [
            'sales'      => $sales,
            'customers'  => $customers,
            'warehouses' => $warehouses,
            'filters'    => $filters,
        ]);
    }

    public function create(AlmacenService $AlmacenService, ProductoService $ProductoService, ClienteService $ClienteService)
    {
        $customers = $ClienteService->getAllCustomers()->items();
        $warehouses = $AlmacenService->getAllWarehouses()->items();
        $medicamentsList = $ProductoService->listForSelect();

        return Inertia::render('Ventas/NotasVenta/Form', [
            'formAction' => 'create',
            'customers' => $customers,
            'warehouses' => $warehouses,
            'medicamentsList' => $medicamentsList,
        ]);
    }

    public function store(StoreNotaVentaRequest $request, NotaVentaService $NotaVentaService)
    {
        $data = $request->validated();
        $data['sale_date'] = now();
        $data['user_id'] = Auth::id();
        try {
            $NotaVentaService->createSalesNoteWithInventory($data);
            // Respondemos JSON para axios
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Nota de venta creada exitosamente',
                    'success' => true,
                ], 201);
            }
            // Redirección si es submit normal
            return redirect()->route('notas-venta.index')->with('success', 'Nota de venta creada exitosamente');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Error al crear la nota de venta: ' . $e->getMessage(),
                    'success' => false,
                ], 500);
            }
            return redirect()->back()->withErrors(['error' => 'Error al crear la nota de venta: ' . $e->getMessage()]);
        }
    }

    public function show($id, DetalleNotaVentaService $DetalleNotaVentaService)
    {
        $salesNote = $this->NotaVentaService->getSalesNoteById($id);
        $salesNoteDetails = $DetalleNotaVentaService->getSalesNoteDetailsBySalesNoteId($id);

        // SIEMPRE responde JSON
        return response()->json([
            'salesNote' => $salesNote,
            'salesNoteDetails' => $salesNoteDetails,
        ]);
    }

    public function edit(
        int $id,
        AlmacenService $AlmacenService,
        ProductoService $ProductoService,
        ClienteService $ClienteService,
        DetalleNotaVentaService $detailService
    ) {
        $salesNote        = $this->NotaVentaService->getSalesNoteById($id);
        $salesNoteDetails = $detailService->getSalesNoteDetailsBySalesNoteId($id);

        $customers     = $ClienteService->getAllCustomers()->items();
        $warehouses    = $AlmacenService->getAllWarehouses()->items();
        $medicaments   = $ProductoService->listForSelect();

        return Inertia::render('Ventas/NotasVenta/FormEdit', [
            'salesNote'         => $salesNote,
            'salesNoteDetails'  => $salesNoteDetails,
            'customers'         => $customers,
            'warehouses'        => $warehouses,
            'medicamentsList'   => $medicaments,
        ]);
    }

    public function update(UpdateNotaVentaRequest $request, $id)
    {
        $data = $request->validated();

        try {
            $this->NotaVentaService->updateSalesNoteWithInventory($id, $data);

            // Responde SIEMPRE JSON (para Axios/Toast)
            return response()->json([
                'message' => 'Nota de venta actualizada exitosamente',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la nota de venta',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function generatePdf($id, DetalleNotaVentaService $DetalleNotaVentaService)
    {
        $salesNote = $this->NotaVentaService->getSalesNoteById($id);
        $salesNoteDetails = $DetalleNotaVentaService->getSalesNoteDetailsBySalesNoteId($id);

        $pdf = PDF::loadView('pdf.sales_note', compact('salesNote', 'salesNoteDetails'));
        return $pdf->stream('nota_de_venta.pdf');
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $salesNote = NotaVenta::with('salesNoteDetails')->findOrFail($id);
            foreach ($salesNote->salesNoteDetails as $detail) {
                $this->InventarioService->restoreStockForSalesDetail($detail->id);
                $detail->delete();
            }
            $salesNote->delete();

            DB::commit();
            // RESPONDE JSON (no redirect)
            return response()->json([
                'message' => "Venta eliminada exitosamente",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar la venta',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function report(Request $request)
    {
        $filters = $request->only([
            'customer_id',
            'warehouse_id',
            'date_from',
            'date_to',
        ]);

        $sales = $this->NotaVentaService->getFilteredSalesNotes($filters, false);

        $pdf = PDF::loadView('pdf.sales_notes_report', compact('sales', 'filters'))
            ->setPaper('a4', 'landscape');

        return $pdf->stream('reporte_ventas_' . now()->format('Ymd') . '.pdf');
    }
}
