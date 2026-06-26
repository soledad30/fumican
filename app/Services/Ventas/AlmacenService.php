<?php

namespace App\Services\Ventas;

use App\Repositories\Ventas\AlmacenRepository;

class AlmacenService
{
    public function __construct(protected AlmacenRepository $AlmacenRepository) {}

    public function getAllWarehouses()
    {
        return $this->AlmacenRepository->getAll();
    }

    public function getAllWarehousesWithoutPaginate()
    {
        return $this->AlmacenRepository->getAllWithoutPaginate();
    }

    public function getWarehouseById($id)
    {
        return $this->AlmacenRepository->findById($id);
    }

    public function createWarehouse(array $userData)
    {
        return $this->AlmacenRepository->create($userData);
    }

    public function updateWarehouse(int $id, array $data)
    {
        return $this->AlmacenRepository->update($id, $data);
    }

    public function deleteWarehouse(int $id)
    {
        return $this->AlmacenRepository->delete($id);
    }

    public function search()
    {
        return $this->AlmacenRepository->search();
    }

    public function getGroupedInventories($id)
    {
        return $this->AlmacenRepository->getGroupedInventories($id);
    }
}
