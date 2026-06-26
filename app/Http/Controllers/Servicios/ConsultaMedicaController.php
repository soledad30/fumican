<?php

namespace App\Http\Controllers\Servicios;

use App\Enums\EstadoConsultaEnum;
use App\Enums\MetodoPagoEnum;
use App\Enums\TipoPagoEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Servicios\CambiarEstadoConsultaRequest;
use App\Http\Requests\Servicios\StoreConsultaMedicaRequest;
use App\Http\Requests\Servicios\UpdateConsultaMedicaRequest;
use App\Models\Servicios\Mascota;
use App\Repositories\Servicios\ServicioRepository;
use App\Services\Servicios\ConsultaMedicaService;
use App\Services\Servicios\HistorialClinicoService;
use App\Support\AutorizaMascotaCliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;
use PDF;

class ConsultaMedicaController extends Controller
{
    public function __construct(
        protected ConsultaMedicaService $mcService,
        protected ServicioRepository $servicioRepository,
        protected HistorialClinicoService $historialService
    ) {}

    private function datosConsultasIndex($medicalConsultations, array $filters = []): array
    {
        return [
            'medicalConsultations' => $medicalConsultations,
            'servicios' => $this->servicioRepository->getAllActivos(),
            'estadosConsulta' => EstadoConsultaEnum::labels(),
            'metodosPago' => MetodoPagoEnum::labels(),
            'tiposPago' => TipoPagoEnum::labels(),
            'filters' => $filters,
        ];
    }

    public function index(): InertiaResponse
    {
        return Inertia::render(
            'Servicios/ConsultasMedicas/Index',
            $this->datosConsultasIndex($this->mcService->getAllWithDetails())
        );
    }

    public function search(Request $request): InertiaResponse
    {
        $filters = $request->only('search_term', 'date_from', 'date_to', 'estado');

        return Inertia::render(
            'Servicios/ConsultasMedicas/Index',
            $this->datosConsultasIndex($this->mcService->search($filters), $filters)
        );
    }

    public function store(StoreConsultaMedicaRequest $request): JsonResponse
    {
        $consultation = $this->mcService->create($request->validated());

        if ($peso = $request->input('weight')) {
            Mascota::where('id', $request->input('pet_id'))->update(['peso' => $peso]);
        }

        return response()->json([
            'message' => 'Consulta creada correctamente.',
            'consultation' => $consultation,
        ], 201);
    }

    public function update(UpdateConsultaMedicaRequest $request, string $id): JsonResponse
    {
        $this->mcService->update($request->validated(), $id);

        if ($peso = $request->input('weight')) {
            Mascota::where('id', $request->input('pet_id'))->update(['peso' => $peso]);
        }

        return response()->json([
            'message' => 'Consulta actualizada correctamente.',
        ]);
    }

    public function cambiarEstado(CambiarEstadoConsultaRequest $request, int $id): JsonResponse
    {
        try {
            $consulta = $this->mcService->cambiarEstado(
                $id,
                $request->validated('estado'),
                $request->boolean('emergencia')
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Estado actualizado correctamente.',
            'consultation' => $consulta,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->mcService->delete($id);

        return response()->json([
            'message' => 'Consulta eliminada correctamente.',
        ]);
    }

    public function generateConsultationsReport(Request $request)
    {
        $filters = $request->only('search_term', 'date_from', 'date_to', 'estado');
        $consultations = $this->mcService->getFilteredResults($filters);

        $pdf = PDF::loadView('pdf.consultations_report', compact('consultations', 'filters'));

        return $pdf->stream('reporte_consultas_'.now()->format('Ymd').'.pdf');
    }

    public function generatePetHistoryReport(Request $request, Mascota $pet)
    {
        if (! AutorizaMascotaCliente::puedeVer($request->user(), $pet)) {
            abort(403);
        }

        $historial = $this->historialService->obtenerHistorial($pet);

        $pdf = PDF::loadView('pdf.pet_clinical_history', $historial);

        return $pdf->setPaper('a4', 'portrait')->stream('historial_clinico_'.$pet->name.'.pdf');
    }
}
