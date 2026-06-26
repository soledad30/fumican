<?php

namespace App\Services\Ventas;

use App\Models\Ventas\NotaVenta;
use App\Models\Ventas\DetalleNotaVenta;
use App\Repositories\Ventas\NotaVentaRepository;
use Illuminate\Support\Facades\DB;

class NotaVentaService
{
    public function __construct(protected NotaVentaRepository $NotaVentaRepository, protected InventarioService $InventarioService) {}

    public function getAllSalesNote()
    {
        return $this->NotaVentaRepository->getAll();
    }

    public function getSalesNoteById($id)
    {
        return $this->NotaVentaRepository->findById($id);
    }

    public function createSalesNoteDetail(array $salesNoteData)
    {
        return $this->NotaVentaRepository->create($salesNoteData);
    }

    public function createSalesNoteWithInventory(array $salesNoteData)
    {
        DB::beginTransaction();
        try {
            $salesNote = NotaVenta::create([
                'fecha_venta' => $salesNoteData['sale_date'] ?? $salesNoteData['fecha_venta'] ?? now(),
                'monto_total' => $salesNoteData['total_amount'] ?? $salesNoteData['monto_total'],
                'estado' => $salesNoteData['estado'] ?? 'completada',
                'cliente_id' => $salesNoteData['customer_id'] ?? $salesNoteData['cliente_id'],
                'usuario_id' => $salesNoteData['user_id'] ?? $salesNoteData['usuario_id'],
                'almacen_id' => $salesNoteData['warehouse_id'] ?? $salesNoteData['almacen_id'],
            ]);

            foreach ($salesNoteData['medicaments'] as $medicament) {
                // Crear el detalle de la venta
                $saleDetailData = [
                    'nota_venta_id' => $salesNote->id,
                    'producto_id' => $medicament['id'],
                    'cantidad' => $medicament['quantity'],
                    'precio_venta' => $medicament['sale_price'],
                    'subtotal' => $medicament['subtotal'],
                ];
                $salesNoteDetail = DetalleNotaVenta::create($saleDetailData);

                // 2.2) Consumir stock usando FIFO
                $this->InventarioService->consumeStock(
                    $salesNoteData['almacen_id'] ?? $salesNoteData['warehouse_id'],
                    $medicament['id'],
                    $medicament['quantity'],
                    $salesNoteDetail->id
                );
            }

            DB::commit();
            return $salesNote;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateSalesNoteWithInventory(int $id, array $data)
    {
        DB::beginTransaction();
        try {
            $salesNote = NotaVenta::findOrFail($id);
            $salesNote->update([
                'cliente_id' => $data['cliente_id'] ?? $data['customer_id'],
                'almacen_id' => $data['almacen_id'] ?? $data['warehouse_id'],
                'monto_total' => $data['monto_total'] ?? $data['total_amount'],
            ]);

            // 1) Restaurar stock y borrar detalles viejos
            foreach ($salesNote->salesNoteDetails as $old) {
                $this->InventarioService->restoreStockForSalesDetail($old->id);
                $old->delete();
            }

            // 2) Crear nuevos detalles y consumir stock FIFO
            foreach ($data['medicaments'] as $m) {
                $detail = DetalleNotaVenta::create([
                    'nota_venta_id' => $id,
                    'producto_id' => $m['id'],
                    'cantidad' => $m['quantity'],
                    'precio_venta' => $m['sale_price'],
                    'subtotal' => $m['subtotal'],
                ]);
                $this->InventarioService->consumeStock(
                    $salesNote->almacen_id,
                    $m['id'],
                    $m['quantity'],
                    $detail->id
                );
            }

            DB::commit();
            return $salesNote;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getFilteredSalesNotes($filters, $paginate = true)
    {
        $query = NotaVenta::with([
            'cliente:id,nombre,apellido',
            'almacen:id,nombre,ubicacion,descripcion',
        ]);

        if (! empty($filters['customer_id'])) {
            $query->where('cliente_id', $filters['customer_id']);
        }
        if (! empty($filters['warehouse_id'])) {
            $query->where('almacen_id', $filters['warehouse_id']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('fecha_venta', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('fecha_venta', '<=', $filters['date_to']);
        }

        $perPage = $filters['per_page'] ?? 15;
        if ($paginate) {
            return $query->orderByDesc('fecha_venta')->orderByDesc('creado_en')->paginate($perPage);
        }

        return $query->orderByDesc('fecha_venta')->orderByDesc('creado_en')->get();
    }
}
