<?php

namespace App\Http\Controllers\Servicios;

use App\Enums\EstadoConsultaEnum;
use App\Enums\MetodoPagoEnum;
use App\Enums\TipoPagoEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Servicios\CambiarEstadoConsultaRequest;
use App\Http\Requests\Servicios\IniciarAtencionRequest;
use App\Http\Requests\Servicios\ReprogramarConsultaRequest;
use App\Http\Requests\Servicios\StoreConsultaMedicaRequest;
use App\Http\Requests\Servicios\UpdateConsultaMedicaRequest;
use App\Models\Servicios\ConsultaMedica;
use App\Models\Servicios\Mascota;
use App\Repositories\Servicios\ServicioRepository;
use App\Services\Servicios\ConsultaMedicaService;
use App\Services\Servicios\HistorialClinicoService;
use App\Services\Servicios\RegistroLlegadaService;
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
        protected HistorialClinicoService $historialService,
        protected RegistroLlegadaService $registroLlegadaService
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
            'minutosGracia' => config('reservas.minutos_gracia_no_asistio', 20),
            'horaCierre' => config('reservas.hora_cierre_clinica', '19:00'),
            'horariosCita' => config('reservas.horarios', []),
        ];
    }

    public function index(Request $request): InertiaResponse
    {
        return Inertia::render(
            'Servicios/ConsultasMedicas/Index',
            array_merge(
                $this->datosConsultasIndex($this->mcService->getAllWithDetails()),
                $this->consultaAccionRapida($request)
            )
        );
    }

    public function search(Request $request): InertiaResponse
    {
        $filters = $request->only(
            'search_term', 'date_from', 'date_to', 'estado',
            'fecha', 'propietario', 'mascota', 'servicio_id'
        );

        return Inertia::render(
            'Servicios/ConsultasMedicas/Index',
            array_merge(
                $this->datosConsultasIndex($this->mcService->search($filters), $filters),
                $this->consultaAccionRapida($request)
            )
        );
    }

    private function consultaAccionRapida(Request $request): array
    {
        $relaciones = ['mascota.propietario', 'mascota.raza.especie', 'servicio', 'veterinario', 'pagos'];

        return [
            'atenderConsulta' => $request->filled('atender')
                ? ConsultaMedica::with($relaciones)->find($request->integer('atender'))
                : null,
            'cobrarConsulta' => $request->filled('cobrar')
                ? ConsultaMedica::with($relaciones)->find($request->integer('cobrar'))
                : null,
        ];
    }

    public function agenda(Request $request): InertiaResponse
    {
        $fecha = $request->input('fecha', now()->toDateString());
        $fechaRecordatorios = $request->input('fecha_recordatorios', now()->addDay()->toDateString());

        return Inertia::render('Servicios/ConsultasMedicas/Agenda', [
            'agenda' => $this->mcService->getAgendaDia($fecha),
            'recordatorios' => $this->mcService->getRecordatorios($fechaRecordatorios),
            'estadosConsulta' => EstadoConsultaEnum::labels(),
            'horarios' => config('reservas.horarios', []),
            'fecha' => $fecha,
            'fechaRecordatorios' => $fechaRecordatorios,
            'minutosGracia' => config('reservas.minutos_gracia_no_asistio', 20),
            'horaCierre' => config('reservas.hora_cierre_clinica', '19:00'),
        ]);
    }

    public function store(StoreConsultaMedicaRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (($data['estado'] ?? '') === EstadoConsultaEnum::COMPLETADA->value) {
            $data['fecha'] ??= now()->toDateString();
            $data['hora'] ??= now()->format('H:i:s');
        }

        $consultation = $this->mcService->create($data);

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
        $data = $request->validated();
        if (($data['estado'] ?? '') === EstadoConsultaEnum::COMPLETADA->value) {
            $data['fecha'] ??= now()->toDateString();
            $data['hora'] ??= now()->format('H:i:s');
        }

        $this->mcService->update($data, $id);

        if ($peso = $request->input('weight')) {
            Mascota::where('id', $request->input('pet_id'))->update(['peso' => $peso]);
        }

        return response()->json([
            'message' => 'Consulta actualizada correctamente.',
        ]);
    }

    public function iniciarAtencion(IniciarAtencionRequest $request): JsonResponse
    {
        $consulta = $this->mcService->iniciarAtencion(
            (int) $request->validated('pet_id'),
            $request->validated('service_id'),
            $request->validated('reason')
        );

        return response()->json([
            'message' => 'Atención iniciada. Complete la ficha clínica.',
            'consultation' => $consulta,
        ], 201);
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

    public function completarRegistroLlegada(CompletarRegistroLlegadaRequest $request, int $id): JsonResponse
    {
        try {
            $consulta = $this->registroLlegadaService->completarRegistroYAtender(
                $id,
                $request->validated(),
                $request->boolean('emergencia')
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Cliente y mascota registrados. Paciente en sala de espera.',
            'consultation' => $consulta,
        ]);
    }

    public function registrarLlegada(Request $request, int $id): JsonResponse
    {
        try {
            $consulta = $this->mcService->registrarLlegada($id, $request->boolean('emergencia'));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Check-in realizado. Paciente en sala de espera.',
            'consultation' => $consulta,
        ]);
    }

    public function reprogramar(ReprogramarConsultaRequest $request, int $id): JsonResponse
    {
        try {
            $consulta = $this->mcService->reprogramar(
                $id,
                $request->validated('fecha'),
                $request->validated('hora')
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Cita reprogramada. El anticipo anterior se perdió por no asistencia; debe pagar anticipo nuevamente.',
            'consultation' => $consulta,
        ]);
    }

    public function reprogramarPorTarde(ReprogramarConsultaRequest $request, int $id): JsonResponse
    {
        try {
            $consulta = $this->mcService->reprogramarPorTarde(
                $id,
                $request->validated('fecha'),
                $request->validated('hora')
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Cita reprogramada por llegada tarde. El anticipo se conserva (única reprogramación permitida).',
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
        $filters = $request->only(
            'search_term', 'date_from', 'date_to', 'estado',
            'fecha', 'propietario', 'mascota', 'servicio_id'
        );
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
