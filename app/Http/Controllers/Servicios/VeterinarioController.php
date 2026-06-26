<?php

namespace App\Http\Controllers\Servicios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Servicios\StoreVeterinarioRequest;
use App\Http\Requests\Servicios\UpdateVeterinarioRequest;
use App\Models\Servicios\Veterinario;
use App\Services\Servicios\VeterinarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class VeterinarioController extends Controller
{
    public function __construct(protected VeterinarioService $service) {}

    public function index(): InertiaResponse
    {
        return Inertia::render('Servicios/Veterinarios/Index', [
            'veterinarios' => $this->service->listar(),
            'especialidades' => $this->service->listarEspecialidades(),
            'filters' => [],
        ]);
    }

    public function search(Request $request): InertiaResponse
    {
        $filters = $request->only('search_term');

        return Inertia::render('Servicios/Veterinarios/Index', [
            'veterinarios' => $this->service->buscar($filters),
            'especialidades' => $this->service->listarEspecialidades(),
            'filters' => $filters,
        ]);
    }

    public function store(StoreVeterinarioRequest $request): JsonResponse
    {
        $veterinario = $this->service->crear($request->validated());

        return response()->json([
            'message' => 'Veterinario registrado correctamente.',
            'veterinario' => $veterinario,
        ], 201);
    }

    public function update(UpdateVeterinarioRequest $request, Veterinario $veterinario): JsonResponse
    {
        $this->service->actualizar($veterinario->id, $request->validated());

        return response()->json(['message' => 'Veterinario actualizado correctamente.']);
    }

    public function destroy(Veterinario $veterinario): JsonResponse
    {
        $this->service->eliminar($veterinario->id);

        return response()->json(['message' => 'Veterinario eliminado correctamente.']);
    }
}
