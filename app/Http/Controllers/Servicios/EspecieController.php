<?php

namespace App\Http\Controllers\Servicios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Servicios\StoreEspecieRequest;
use App\Http\Requests\Servicios\UpdateEspecieRequest;
use App\Services\Servicios\EspecieService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class EspecieController extends Controller
{
    public function __construct(protected EspecieService $service) {}

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json($this->service->getAll());
        }

        return Inertia::render('Servicios/Especies/Index', [
            'especies' => $this->service->getAll(),
        ]);
    }

    public function store(StoreEspecieRequest $request): JsonResponse
    {
        $especie = $this->service->create($request->validated());

        return response()->json([
            'message' => 'Especie registrada correctamente.',
            'especie' => $especie,
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json($this->service->getById($id));
    }

    public function update(UpdateEspecieRequest $request, string $id): JsonResponse
    {
        $this->service->update($id, $request->validated());

        return response()->json(['message' => 'Especie actualizada correctamente.']);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(['message' => 'Especie eliminada correctamente.']);
    }

    public function search(): JsonResponse
    {
        return response()->json($this->service->search());
    }
}
