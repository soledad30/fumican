<?php

namespace App\Services\Ventas;

use App\Repositories\Ventas\ProductoRepository;

class ProductoService
{
    public function __construct(protected ProductoRepository $ProductoRepository) {}

    public function getAllMedicaments()
    {
        return $this->ProductoRepository->getAll();
    }

    public function listForSelect()
    {
        return $this->ProductoRepository->listForSelect();
    }

    public function getMedicamentById($id)
    {
        return $this->ProductoRepository->findById($id);
    }

    public function createMedicament(array $userData)
    {
        return $this->ProductoRepository->create($userData);
    }

    public function search(array $filters)
    {
        return $this->ProductoRepository->search($filters);
    }

    public function updateMedicament($id, array $data)
    {
        return $this->ProductoRepository->update($id, $data);
    }

    public function deleteMedicament($id)
    {
        return $this->ProductoRepository->delete($id);
    }

    public function getFilteredMedicaments(array $filters)
    {
        return $this->ProductoRepository->filtered($filters);
    }

    public function listarFabricantes(): array
    {
        return $this->ProductoRepository->fabricantesDistintos();
    }

    public function actualizarReferenciaPostCompra(int $productoId, float $precioVenta, ?string $fechaVencimiento = null): void
    {
        $data = ['precio_venta_referencia' => $precioVenta];
        if ($fechaVencimiento) {
            $data['fecha_vencimiento'] = $fechaVencimiento;
        }
        $this->ProductoRepository->update($productoId, $data);
    }
}
