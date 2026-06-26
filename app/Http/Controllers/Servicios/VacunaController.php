<?php

namespace App\Http\Controllers\Servicios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Servicios\StoreHistorialVacunacionRequest;
use App\Http\Requests\Servicios\StoreServicioRequest;
use App\Http\Requests\Servicios\StoreVacunaRequest;
use App\Http\Requests\Servicios\UpdateHistorialVacunacionRequest;
use App\Http\Requests\Servicios\UpdateServicioRequest;
use App\Http\Requests\Servicios\UpdateVacunaRequest;
use App\Services\Servicios\HistorialVacunacionService;
use App\Services\Servicios\MascotaService;
use App\Services\Servicios\VacunaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class VacunaController extends Controller
{
    public function __construct(
        protected VacunaService $vacunaService,
        protected HistorialVacunacionService $historialService,
        protected MascotaService $mascotaService
    ) {}

    public function index(): InertiaResponse
    {
        return Inertia::render('Servicios/Vacunas/Index', [
            'vacunas' => $this->vacunaService->getAll(),
            'historial' => $this->historialService->getAll(),
            'vacunasOpciones' => $this->vacunaService->getAllSinPaginar(),
            'mascotasOpciones' => $this->mascotaService->listForSelect(),
            'filters' => [],
        ]);
    }

    public function search(Request $request): InertiaResponse
    {
        $filters = $request->only('search_term');

        return Inertia::render('Servicios/Vacunas/Index', [
            'vacunas' => $this->vacunaService->search($filters['search_term'] ?? null),
            'historial' => $this->historialService->getAll(),
            'vacunasOpciones' => $this->vacunaService->getAllSinPaginar(),
            'mascotasOpciones' => $this->mascotaService->listForSelect(),
            'filters' => $filters,
        ]);
    }

    public function store(StoreVacunaRequest $request): JsonResponse
    {
        $vacuna = $this->vacunaService->create($request->validated());

        return response()->json(['message' => 'Vacuna registrada correctamente.', 'vacuna' => $vacuna], 201);
    }

    public function update(UpdateVacunaRequest $request, int $id): JsonResponse
    {
        $this->vacunaService->update($id, $request->validated());

        return response()->json(['message' => 'Vacuna actualizada correctamente.']);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->vacunaService->delete($id);

        return response()->json(['message' => 'Vacuna eliminada correctamente.']);
    }

    public function storeHistorial(StoreHistorialVacunacionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['aplicado_por'] = Auth::id();
        $registro = $this->historialService->create($data);

        return response()->json(['message' => 'Historial de vacunación registrado.', 'historial' => $registro], 201);
    }

    public function updateHistorial(UpdateHistorialVacunacionRequest $request, int $id): JsonResponse
    {
        $this->historialService->update($id, $request->validated());

        return response()->json(['message' => 'Historial actualizado correctamente.']);
    }

    public function destroyHistorial(int $id): JsonResponse
    {
        $this->historialService->delete($id);

        return response()->json(['message' => 'Registro de historial eliminado.']);
    }
}
