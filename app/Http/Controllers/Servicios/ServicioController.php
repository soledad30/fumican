<?php

namespace App\Http\Controllers\Servicios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Servicios\StoreServicioRequest;
use App\Http\Requests\Servicios\UpdateServicioRequest;
use App\Services\Servicios\ServicioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ServicioController extends Controller
{
    public function __construct(protected ServicioService $servicioService) {}

    public function index(): InertiaResponse
    {
        return Inertia::render('Servicios/Servicios/Index', [
            'servicios' => $this->servicioService->getAll(),
            'filters' => [],
        ]);
    }

    public function search(Request $request): InertiaResponse
    {
        $filters = $request->only('search_term');

        return Inertia::render('Servicios/Servicios/Index', [
            'servicios' => $this->servicioService->search($filters['search_term'] ?? null),
            'filters' => $filters,
        ]);
    }

    public function store(StoreServicioRequest $request): JsonResponse
    {
        $servicio = $this->servicioService->create($request->validated());

        return response()->json(['message' => 'Servicio registrado correctamente.', 'servicio' => $servicio], 201);
    }

    public function update(UpdateServicioRequest $request, int $id): JsonResponse
    {
        $this->servicioService->update($id, $request->validated());

        return response()->json(['message' => 'Servicio actualizado correctamente.']);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->servicioService->delete($id);

        return response()->json(['message' => 'Servicio eliminado correctamente.']);
    }
}
