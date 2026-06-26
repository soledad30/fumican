<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Services\Auditoria\BitacoraService;
use App\Services\Auditoria\VisitaService;
use App\Services\Servicios\PagoService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReporteController extends Controller
{
    public function __construct(
        protected BitacoraService $bitacoraService,
        protected VisitaService $visitaService,
        protected PagoService $pagoService
    ) {}

    public function index(): InertiaResponse
    {
        $acceso = $this->bitacoraService->getEstadisticasAcceso();

        return Inertia::render('Reportes/Index', [
            'estadisticasNegocio' => [
                'total_clientes' => DB::table('clientes')->count(),
                'total_mascotas' => DB::table('mascotas')->count(),
                'total_consultas' => DB::table('consultas_medicas')->count(),
                'total_productos' => DB::table('productos')->count(),
                'pagos' => $this->pagoService->getEstadisticas(),
            ],
            'estadisticasAcceso' => $acceso,
            'visitasTop' => $this->visitaService->getTopPaginas(10),
            'visitasTotal' => $this->visitaService->getTotalSitio(),
        ]);
    }
}
