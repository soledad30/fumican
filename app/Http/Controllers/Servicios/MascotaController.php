<?php

namespace App\Http\Controllers\Servicios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Servicios\StoreMascotaRequest;
use App\Http\Requests\Servicios\UpdateMascotaRequest;
use App\Models\Servicios\Mascota;
use App\Services\Servicios\CarnetSanitarioService;
use App\Services\Servicios\EspecieService;
use App\Services\Servicios\HistorialClinicoService;
use App\Services\Servicios\MascotaService;
use App\Services\Servicios\RazaService;
use App\Support\AutorizaMascotaCliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MascotaController extends Controller
{
    public function __construct(
        protected MascotaService $service,
        protected HistorialClinicoService $historialService,
        protected CarnetSanitarioService $carnetService
    ) {}

    public function index(): InertiaResponse
    {
        return Inertia::render('Servicios/Mascotas/Index', [
            'pets' => $this->service->getAll(),
            'filters' => [],
        ]);
    }

    public function search(Request $request): InertiaResponse
    {
        $filters = $request->only('search_term');
        return Inertia::render('Servicios/Mascotas/Index', [
            'pets' => $this->service->search($filters),
            'filters' => $filters,
        ]);
    }

    public function store(StoreMascotaRequest $request): JsonResponse
    {
        $pet = $this->service->create($request->validated(), $request->file('photo'));

        return response()->json(['message' => 'Mascota registrada correctamente.', 'pet' => $pet], 201);
    }

    public function update(UpdateMascotaRequest $request, Mascota $pet): JsonResponse
    {
        $pet = $this->service->update($pet->id, $request->validated(), $request->file('photo'));

        return response()->json(['message' => 'Mascota actualizada correctamente.', 'pet' => $pet]);
    }

    public function destroy(Mascota $pet): JsonResponse
    {
        $this->service->delete($pet->id);
        return response()->json(['message' => 'Mascota eliminada correctamente.']);
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $pets = $this->service->autocompleteSearch($request->search);

        return response()->json($pets);
    }

    public function historial(Mascota $pet): InertiaResponse
    {
        if (! AutorizaMascotaCliente::puedeVer(request()->user(), $pet)) {
            abort(403);
        }

        $historial = $this->historialService->obtenerHistorial($pet);

        return Inertia::render('Servicios/Mascotas/Historial', [
            'mascota' => $historial['mascota'],
            'eventos' => $historial['eventos'],
            'resumen' => $historial['resumen'],
        ]);
    }

    public function resumenClinico(Mascota $pet): JsonResponse
    {
        return response()->json($this->carnetService->resumenClinicoConsulta($pet));
    }

    public function prepareStoreData(Request $request, EspecieService $EspecieService, RazaService $RazaService): JsonResponse
    {
        try {
            $specie = $EspecieService->findOrCreate($request->specie);
            $breed = $RazaService->findOrCreate($request->breed, $specie->id);
            return response()->json(['specie_id' => $specie->id, 'breed_id' => $breed->id]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
