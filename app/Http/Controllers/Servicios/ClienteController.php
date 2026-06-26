<?php

namespace App\Http\Controllers\Servicios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Servicios\StoreClienteRequest;
use App\Http\Requests\Servicios\UpdateClienteRequest;
use App\Models\Servicios\Cliente;
use Illuminate\Http\JsonResponse;
use App\Services\Servicios\ClienteService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ClienteController extends Controller
{
    public function __construct(protected ClienteService $ClienteService) {}

    public function index(): InertiaResponse
    {
        return Inertia::render('Servicios/Clientes/Index', [
            'customers' => $this->ClienteService->getAllCustomers(),
            'filters' => [],
        ]);
    }

    /**
     * CORREGIDO: Este método ahora SÓLO filtra la tabla principal de clientes.
     */
    public function search(Request $request): InertiaResponse
    {
        $filters = $request->only('search_term');
        return Inertia::render('Servicios/Clientes/Index', [
            'customers' => $this->ClienteService->search($filters),
            'filters' => $filters,
        ]);
    }

    /**
     * NUEVO: Este método SÓLO se encarga de las peticiones de autocompletado.
     * Devuelve una respuesta JSON simple y rápida.
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $term = $request->input('search', '');
        $customers = $this->ClienteService->autocompleteSearch($term);
        return response()->json($customers);
    }

    public function store(StoreClienteRequest $request): JsonResponse
    {
        $customer = $this->ClienteService->createCustomer($request->validated());
        return response()->json(['message' => 'Cliente registrado correctamente.', 'customer' => $customer], 201);
    }

    public function update(UpdateClienteRequest $request, Cliente $customer): JsonResponse
    {
        $this->ClienteService->update($request->validated(), $customer->id);
        return response()->json(['message' => 'Cliente actualizado correctamente.']);
    }

    public function destroy(Cliente $customer): JsonResponse
    {
        $this->ClienteService->delete($customer->id);
        return response()->json(['message' => 'Cliente eliminado correctamente.']);
    }
}
