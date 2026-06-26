<?php

namespace App\Services\Servicios;

use App\Repositories\Servicios\ClienteRepository;
use App\Support\LegacyFieldMapper;

class ClienteService
{
    public function __construct(protected ClienteRepository $ClienteRepository) {}

    public function getAllCustomers()
    {
        return $this->ClienteRepository->getAll();
    }

    public function getCustomerById($id)
    {
        return $this->ClienteRepository->findById($id);
    }

    public function createCustomer(array $userData)
    {
        return $this->ClienteRepository->create(LegacyFieldMapper::cliente($userData));
    }

    public function update(array $userData, $id)
    {
        return $this->ClienteRepository->update(LegacyFieldMapper::cliente($userData), $id);
    }

    public function sinUsuario()
    {
        return $this->ClienteRepository->sinUsuario();
    }

    public function delete($id)
    {
        return $this->ClienteRepository->delete($id);
    }

    /**
     * CORREGIDO: Método de búsqueda para la tabla principal (paginado).
     */
    public function search(array $filters)
    {
        return $this->ClienteRepository->search($filters, true);
    }

    /**
     * CORREGIDO: Método para el autocompletado (no paginado).
     */
    public function autocompleteSearch(?string $term)
    {
        $term = trim((string) $term);

        return $this->ClienteRepository->search([
            'search_term' => $term,
            'limit' => $term === '' ? 25 : 15,
        ], false);
    }
}
