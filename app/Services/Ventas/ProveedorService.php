<?php

namespace App\Services\Ventas;

use App\Repositories\Ventas\ProveedorRepository;

class ProveedorService
{
    public function __construct(protected ProveedorRepository $ProveedorRepository) {}

    public function getAllSuppliers()
    {
        return $this->ProveedorRepository->getAll();
    }

    public function getSupplierById($id)
    {
        return $this->ProveedorRepository->findById($id);
    }

    public function createSupplier(array $userData)
    {
        return $this->ProveedorRepository->create($userData);
    }

    public function updateSupplier(int $id, array $userData)
    {
        return $this->ProveedorRepository->update($id, $userData);
    }

    public function deleteSupplier(int $id)
    {
        return $this->ProveedorRepository->findById($id)->delete();
    }

    public function search()
    {
        return $this->ProveedorRepository->search();
    }
}
