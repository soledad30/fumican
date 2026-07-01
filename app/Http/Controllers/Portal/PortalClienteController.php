<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StorePortalReservaRequest;
use App\Models\Servicios\Mascota;
use App\Services\Portal\PortalReservaService;
use App\Services\Servicios\CarnetSanitarioService;
use App\Services\Servicios\HistorialClinicoService;
use App\Services\Servicios\ServicioService;
use App\Support\AutorizaMascotaCliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PortalClienteController extends Controller
{
    public function __construct(
        protected CarnetSanitarioService $carnetService,
        protected HistorialClinicoService $historialService,
        protected ServicioService $servicioService,
        protected PortalReservaService $reservaService
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $cliente = AutorizaMascotaCliente::clienteDeUsuario($request->user());

        if (! $cliente) {
            return Inertia::render('Portal/SinVinculo', [
                'mensaje' => 'Su usuario no está vinculado a una ficha de cliente. Contacte a la clínica.',
            ]);
        }

        return Inertia::render('Portal/Index', [
            ...$this->carnetService->resumenPortalCliente($cliente),
            'servicios' => $this->servicioService->getAllActivos(),
            'horarios' => $this->reservaService->horariosDisponibles(),
            'deudas' => $this->reservaService->deudasCliente($cliente),
            'compras' => $this->reservaService->comprasCliente($cliente),
        ]);
    }

    public function storeReserva(StorePortalReservaRequest $request): JsonResponse
    {
        $cliente = AutorizaMascotaCliente::clienteDeUsuario($request->user());

        if (! $cliente) {
            return response()->json([
                'message' => 'Su usuario no está vinculado a un cliente.',
            ], 403);
        }

        $consulta = $this->reservaService->crearReserva($cliente, $request->validated());

        return response()->json([
            'message' => 'Cita reservada correctamente. Acuda a la clínica en la fecha reservada; el pago se realiza al finalizar el servicio.',
            'consulta' => $consulta->load('servicio:id,nombre,precio'),
        ], 201);
    }

    public function carnet(Request $request, Mascota $mascota): InertiaResponse
    {
        if (! AutorizaMascotaCliente::puedeVer($request->user(), $mascota)) {
            throw new AccessDeniedHttpException('No tiene permiso para ver esta mascota.');
        }

        return Inertia::render('Portal/Carnet', $this->carnetService->carnetMascota($mascota));
    }

    public function carnetPdf(Request $request, Mascota $mascota)
    {
        if (! AutorizaMascotaCliente::puedeVer($request->user(), $mascota)) {
            throw new AccessDeniedHttpException('No tiene permiso para ver esta mascota.');
        }

        $historial = $this->historialService->obtenerHistorial($mascota);
        $pdf = \PDF::loadView('pdf.pet_clinical_history', $historial);

        return $pdf->setPaper('a4', 'portrait')->stream('carnet_'.$mascota->name.'.pdf');
    }
}
