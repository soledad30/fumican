<?php

namespace App\Http\Controllers\Servicios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Servicios\StoreRazaRequest;
use App\Http\Requests\Servicios\UpdateRazaRequest;
use App\Services\Servicios\EspecieService;
use App\Services\Servicios\RazaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class RazaController extends Controller
{
    public function __construct(
        protected RazaService $service,
        protected EspecieService $especieService
    ) {}

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json($this->service->getAll());
        }

        return Inertia::render('Servicios/Razas/Index', [
            'razas' => $this->service->getAll(),
            'especies' => $this->especieService->listAll(),
        ]);
    }

    public function store(StoreRazaRequest $request): JsonResponse
    {
        $raza = $this->service->create($request->validated());

        return response()->json([
            'message' => 'Raza registrada correctamente.',
            'raza' => $raza->load('especie:id,nombre'),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json($this->service->getById($id));
    }

    public function update(UpdateRazaRequest $request, string $id): JsonResponse
    {
        $this->service->update($id, $request->validated());

        return response()->json(['message' => 'Raza actualizada correctamente.']);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(['message' => 'Raza eliminada correctamente.']);
    }

    public function search(): JsonResponse
    {
        return response()->json($this->service->search());
    }
}
