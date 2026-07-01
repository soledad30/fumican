<?php

namespace App\Http\Controllers\Servicios;

use App\Enums\MetodoPagoEnum;
use App\Enums\TipoPagoEnum;
use App\Http\Controllers\Controller;
use App\Models\Ventas\NotaVenta;
use App\Models\Servicios\ConsultaMedica;
use App\Http\Requests\Servicios\StorePagoRequest;
use App\Http\Requests\Servicios\UpdatePagoRequest;
use App\Services\Auditoria\CuotaCreditoService;
use App\Services\Servicios\PagoService;
use App\Support\ConsultaSaldo;
use App\Support\NotaVentaSaldo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PagoController extends Controller
{
    public function __construct(
        protected PagoService $pagoService,
        protected CuotaCreditoService $cuotaCreditoService
    ) {}

    protected function datosPagosIndex(array $filters = []): array
    {
        $notasVenta = NotaVenta::with(['cliente', 'pagos'])
            ->orderByDesc('fecha_venta')
            ->limit(100)
            ->get()
            ->map(function (NotaVenta $nota) {
                $nota->setAttribute('monto_pagado', NotaVentaSaldo::montoPagado($nota));
                $nota->setAttribute('saldo_pendiente', NotaVentaSaldo::saldoPendiente($nota));

                return $nota;
            });

        $consultas = ConsultaMedica::with(['mascota.cliente', 'mascota.propietario', 'servicio', 'pagos'])
            ->orderByDesc('fecha')
            ->limit(100)
            ->get()
            ->map(function (ConsultaMedica $c) {
                $c->setAttribute('monto_pagado', ConsultaSaldo::montoPagado($c));
                $c->setAttribute('saldo_pendiente', ConsultaSaldo::saldoPendiente($c));

                return $c;
            });

        $cuentasPendientes = collect()
            ->merge(
                $consultas
                    ->filter(fn (ConsultaMedica $c) => ConsultaSaldo::saldoPendiente($c) > 0)
                    ->map(fn (ConsultaMedica $c) => [
                        'tipo' => 'consulta',
                        'id' => $c->id,
                        'referencia' => '#'.$c->id,
                        'descripcion' => ($c->servicio?->nombre ?? 'Consulta').' — '.($c->mascota?->nombre ?? 'Mascota'),
                        'cliente' => trim(
                            ($c->mascota?->propietario?->nombre ?? $c->mascota?->cliente?->nombre ?? '')
                            .' '.($c->mascota?->propietario?->apellido ?? $c->mascota?->cliente?->apellido ?? '')
                        ) ?: '—',
                        'total' => (float) ($c->costo_consulta ?? 0),
                        'pagado' => ConsultaSaldo::montoPagado($c),
                        'saldo' => ConsultaSaldo::saldoPendiente($c),
                        'fecha' => $c->fecha ?? $c->creado_en,
                        'creado_en' => $c->creado_en,
                    ])
            )
            ->merge(
                $notasVenta
                    ->filter(fn (NotaVenta $n) => NotaVentaSaldo::saldoPendiente($n) > 0)
                    ->map(fn (NotaVenta $n) => [
                        'tipo' => 'nota',
                        'id' => $n->id,
                        'referencia' => 'Nota #'.$n->id,
                        'descripcion' => 'Venta de productos',
                        'cliente' => trim(($n->cliente?->nombre ?? '').' '.($n->cliente?->apellido ?? '')) ?: '—',
                        'total' => (float) ($n->monto_total ?? 0),
                        'pagado' => NotaVentaSaldo::montoPagado($n),
                        'saldo' => NotaVentaSaldo::saldoPendiente($n),
                        'fecha' => $n->fecha_venta,
                        'creado_en' => $n->creado_en ?? $n->fecha_venta,
                    ])
            )
            ->sortByDesc(fn ($c) => $c['creado_en'] ?? $c['fecha'])
            ->values();

        $estadisticas = $this->pagoService->getEstadisticas();
        $estadisticas['total_pendiente'] = (float) $cuentasPendientes->sum('saldo');
        $estadisticas['cuentas_pendientes'] = $cuentasPendientes->count();

        return [
            'pagos' => empty($filters)
                ? $this->pagoService->getAll()
                : $this->pagoService->search($filters),
            'estadisticas' => $estadisticas,
            'tiposPago' => TipoPagoEnum::labels(),
            'metodosPago' => MetodoPagoEnum::labels(),
            'notasVenta' => $notasVenta,
            'consultas' => $consultas,
            'cuentasPendientes' => $cuentasPendientes,
            'planesPago' => $this->cuotaCreditoService->getPlanesActivos(),
            'filters' => $filters,
        ];
    }

    public function index(): InertiaResponse
    {
        return Inertia::render('Servicios/Pagos/Index', $this->datosPagosIndex());
    }

    public function search(Request $request): InertiaResponse
    {
        return Inertia::render('Servicios/Pagos/Index', $this->datosPagosIndex(
            $request->only('search_term', 'tipo_pago')
        ));
    }

    public function store(StorePagoRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $pago = $this->pagoService->create($data);

            return response()->json(['message' => 'Pago registrado correctamente.', 'pago' => $pago], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }
    }

    public function update(UpdatePagoRequest $request, int $id): JsonResponse
    {
        $this->pagoService->update($id, $request->validated());

        return response()->json(['message' => 'Pago actualizado correctamente.']);
    }

    public function pagarCuota(Request $request, int $cuotaId): JsonResponse
    {
        $data = $request->validate([
            'monto' => 'nullable|numeric|min:0.01',
            'metodo_pago' => 'required|in:efectivo,tarjeta,transferencia,qr',
            'id_transaccion_externa' => 'nullable|string|max:100',
            'fecha_pago' => 'nullable|date',
        ], [
            'metodo_pago.required' => 'Seleccione el método de pago.',
            'monto.min' => 'El monto debe ser mayor a cero.',
        ]);

        $cuota = $this->cuotaCreditoService->registrarPagoCuota($cuotaId, $data);

        return response()->json(['message' => 'Cuota registrada correctamente.', 'cuota' => $cuota]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->pagoService->delete($id);

        return response()->json(['message' => 'Pago eliminado correctamente.']);
    }
}
