<?php

namespace App\Services\Ventas;

use App\Repositories\Ventas\DetalleNotaVentaRepository;

class DetalleNotaVentaService
{
    public function __construct(protected DetalleNotaVentaRepository $DetalleNotaVentaRepository) {}

    public function getAllSalesNoteDetails()
    {
        return $this->DetalleNotaVentaRepository->getAll();
    }

    public function getPurchaseNoteDetailById($id)
    {
        return $this->DetalleNotaVentaRepository->findById($id);
    }

    public function createPurchaseNoteDetail(array $salesNoteDetailData)
    {
        return $this->DetalleNotaVentaRepository->create($salesNoteDetailData);
    }

    public function getSalesNoteDetailsBySalesNoteId($salesNoteId)
    {
        return $this->DetalleNotaVentaRepository->findBySalesNoteId($salesNoteId);
    }
}
