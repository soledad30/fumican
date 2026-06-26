<?php

namespace App\Services\Ventas;

use App\Repositories\Ventas\CategoriaRepository;

class CategoriaService
{
    public function __construct(protected CategoriaRepository $CategoriaRepository) {}

    public function getAllCategories()
    {
        return $this->CategoriaRepository->getAll();
    }

    public function getAllCategoriesWithoutPaginate()
    {
        return $this->CategoriaRepository->getAllWithoutPaginate();
    }

    public function getCategoryById($id)
    {
        return $this->CategoriaRepository->findById($id);
    }

    public function createCategory(array $userData)
    {
        return $this->CategoriaRepository->create($userData);
    }

    public function updateCategory(int $id, array $userData)
    {
        return $this->CategoriaRepository->update($id, $userData);
    }

    public function deleteCategory(int $id)
    {
        return $this->CategoriaRepository->findById($id)->delete();
    }

    public function search(?string $term = null)
    {
        return $this->CategoriaRepository->search($term);
    }
}
