<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Services\Auditoria\BitacoraService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class BitacoraController extends Controller
{
    public function __construct(protected BitacoraService $bitacoraService) {}

    public function index(Request $request): InertiaResponse
    {
        $filters = $request->only('accion', 'modulo', 'search_term', 'fecha_desde', 'fecha_hasta');

        return Inertia::render('Reportes/Bitacora/Index', [
            'registros' => $this->bitacoraService->buscar($filters),
            'estadisticas' => $this->bitacoraService->getEstadisticasAcceso(),
            'filters' => $filters,
            'acciones' => $this->bitacoraService->getAccionesDisponibles(),
            'modulos' => $this->bitacoraService->getModulosDisponibles(),
        ]);
    }
}
