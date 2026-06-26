<?php

namespace App\Services\Ventas;

use App\Models\Ventas\NotaCompra;
use App\Repositories\Ventas\NotaCompraRepository;

class NotaCompraService
{
    public function __construct(protected NotaCompraRepository $NotaCompraRepository) {}

    public function getAllPurchaseNotes(array $filters = [], bool $paginate = true)
    {
        return $this->NotaCompraRepository->getAll($filters, $paginate);
    }

    public function getPurchaseNoteById($id)
    {
        return $this->NotaCompraRepository->findById($id);
    }

    public function createPurchaseNote(array $purchaseNoteData)
    {
        return $this->NotaCompraRepository->create($purchaseNoteData);
    }

    public function updatePurchaseNote($id, array $purchaseNoteData)
    {
        return $this->NotaCompraRepository->update($id, $purchaseNoteData);
    }

    public function deletePurchaseNoteById($id)
    {
        return $this->NotaCompraRepository->deleteById($id);
    }

    public function getFilteredPurchaseNotes($filters, $paginate = true)
    {
        $query = NotaCompra::with(['supplier', 'warehouse']);

        if (! empty($filters['supplier_id'])) {
            $query->where('proveedor_id', $filters['supplier_id']);
        }
        if (! empty($filters['warehouse_id'])) {
            $query->where('almacen_id', $filters['warehouse_id']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('fecha_compra', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('fecha_compra', '<=', $filters['date_to']);
        }

        $perPage = $filters['per_page'] ?? 15;
        if ($paginate) {
            return $query->orderBy('id', 'desc')->paginate($perPage);
        } else {
            return $query->orderBy('id', 'desc')->get();
        }
    }
}
