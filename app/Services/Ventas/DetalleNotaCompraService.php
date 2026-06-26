<?php

namespace App\Services\Ventas;

use App\Repositories\Ventas\DetalleNotaCompraRepository;

class DetalleNotaCompraService
{
    public function __construct(protected DetalleNotaCompraRepository $DetalleNotaCompraRepository) {}

    public function getAllPurchaseNoteDetails()
    {
        return $this->DetalleNotaCompraRepository->getAll();
    }

    public function getPurchaseNoteDetailById($id)
    {
        return $this->DetalleNotaCompraRepository->findById($id);
    }

    public function createPurchaseNoteDetail(array $purchaseNoteDetailData)
    {
        return $this->DetalleNotaCompraRepository->create($purchaseNoteDetailData);
    }

    public function getPurchaseNoteDetailsByPurchaseNoteId($purchaseNoteId)
    {
        return $this->DetalleNotaCompraRepository->findByPurchaseNoteId($purchaseNoteId);
    }

    public function deleteByMedicamentAndPurchaseNoteId($medicamentId, $purchaseNoteId)
    {
        return $this->DetalleNotaCompraRepository->deleteByMedicamentAndPurchaseNoteId($medicamentId, $purchaseNoteId);
    }

    public function updatePurchaseNoteDetail($id, array $purchaseNoteDetailData)
    {
        return $this->DetalleNotaCompraRepository->update($id, $purchaseNoteDetailData);
    }

    public function deleteById($id)
    {
        return $this->DetalleNotaCompraRepository->deleteById($id);
    }
}
