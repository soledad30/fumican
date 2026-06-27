<?php

namespace App\Services\Servicios;

use App\Repositories\Servicios\PagoRepository;
use App\Models\Servicios\ConsultaMedica;
use App\Models\Servicios\Pago;
use App\Models\Ventas\NotaVenta;
use App\Services\Auditoria\CuotaCreditoService;
use App\Support\ConsultaSaldo;
use App\Support\NotaVentaSaldo;
use App\Traits\RegistraBitacora;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PagoService
{
    use RegistraBitacora;

    public function __construct(
        protected PagoRepository $repository,
        protected CuotaCreditoService $cuotaCreditoService,
    ) {}

    public function getAll()
    {
        return $this->repository->getAll();
    }

    public function getEstadisticas(): array
    {
        return $this->repository->estadisticas();
    }

    public function create(array $data): Pago
    {
        $data['usuario_id'] = $data['usuario_id'] ?? Auth::id();
        $saldoPendiente = $this->enriquecerYValidarOrigen($data);

        if (($data['tipo_pago'] ?? '') === 'credito') {
            return $this->crearPagoCredito($data, $saldoPendiente);
        }

        if (empty($data['fecha_pago'])) {
            $data['fecha_pago'] = now();
        }

        return $this->guardarPago($data);
    }

    public function update(int $id, array $data)
    {
        $anterior = $this->repository->findById($id);

        if ($data['tipo_pago'] === 'contado' && empty($data['fecha_pago'])) {
            $data['fecha_pago'] = now();
        }

        $this->repository->update($id, $data);
        $this->registrarBitacora('editar', 'pagos', "Pago actualizado #{$id}", $anterior->toArray(), $data);

        return true;
    }

    public function delete(int $id)
    {
        $pago = $this->repository->findById($id);
        $this->repository->delete($id);
        $this->registrarBitacora('eliminar', 'pagos', "Pago eliminado #{$id}", $pago->toArray());

        return true;
    }

    public function search(array $filters)
    {
        return $this->repository->search($filters);
    }

    private function enriquecerYValidarOrigen(array &$data): float
    {
        $saldo = 0.0;

        if (! empty($data['nota_venta_id'])) {
            $nota = NotaVenta::with('cliente')->find($data['nota_venta_id']);
            if ($nota) {
                $data['cliente_id'] ??= $nota->cliente_id;
                $saldo = NotaVentaSaldo::saldoPendiente($nota);
                $data['monto'] ??= $saldo;

                if (empty($data['concepto_pago'])) {
                    $data['concepto_pago'] = NotaVentaSaldo::montoPagado($nota) > 0 ? 'saldo' : 'completo';
                }

                $this->validarMontoContraSaldo($data, $saldo);
            }
        }

        if (! empty($data['consulta_id'])) {
            $consulta = ConsultaMedica::with('mascota')->find($data['consulta_id']);
            if ($consulta) {
                $data['servicio_id'] ??= $consulta->servicio_id;
                $data['mascota_id'] ??= $consulta->mascota_id;
                $data['cliente_id'] ??= $consulta->mascota?->cliente_id;
                $saldo = ConsultaSaldo::saldoPendiente($consulta);
                $data['monto'] ??= $saldo;

                if (empty($data['concepto_pago'])) {
                    $data['concepto_pago'] = ConsultaSaldo::montoPagado($consulta) > 0 ? 'saldo' : 'completo';
                }

                $this->validarMontoContraSaldo($data, $saldo);
            }
        }

        return $saldo;
    }

    private function validarMontoContraSaldo(array $data, float $saldo): void
    {
        if ((float) $data['monto'] > $saldo + 0.009) {
            throw ValidationException::withMessages([
                'monto' => 'El monto no puede superar el saldo pendiente (Bs. '.number_format($saldo, 2, '.', '').').',
            ]);
        }
    }

    private function crearPagoCredito(array $data, float $saldoPendiente): Pago
    {
        if (! empty($data['cuotas_plan']) && is_array($data['cuotas_plan'])) {
            return $this->crearPagoCreditoConPlan($data, $saldoPendiente);
        }

        $monto = (float) ($data['monto'] ?? $saldoPendiente);
        $numCuotas = (int) ($data['num_cuotas'] ?? 1);
        $abonoHoy = $this->esMetodoInmediato($data['metodo_pago'] ?? '')
            && $monto < $saldoPendiente - 0.009;

        if ($abonoHoy) {
            return DB::transaction(function () use ($data, $monto, $saldoPendiente, $numCuotas) {
                $pagoInmediato = $this->guardarPago(array_merge($data, [
                    'tipo_pago' => 'contado',
                    'monto' => $monto,
                    'fecha_pago' => $data['fecha_pago'] ?? now(),
                    'concepto_pago' => 'anticipo',
                ]));

                $restante = round($saldoPendiente - $monto, 2);
                if ($restante > 0 && $numCuotas > 1) {
                    $this->crearPlanCredito(array_merge($data, [
                        'monto' => $restante,
                        'num_cuotas' => $numCuotas,
                        'concepto_pago' => 'saldo',
                    ]));
                }

                return $pagoInmediato;
            });
        }

        return DB::transaction(function () use ($data, $monto, $numCuotas) {
            $pago = $this->guardarPago(array_merge($data, [
                'tipo_pago' => 'credito',
                'monto' => $monto,
                'fecha_pago' => null,
            ]));

            if ($numCuotas > 1) {
                $this->cuotaCreditoService->crearCuotas($data, $pago->id);
            }

            return $pago;
        });
    }

    /**
     * @param  array<int, array{monto: float|int, fecha: string}>  $cuotasPlan
     */
    private function crearPagoCreditoConPlan(array $data, float $saldoPendiente): Pago
    {
        $cuotasPlan = array_values($data['cuotas_plan']);
        $suma = round(array_sum(array_column($cuotasPlan, 'monto')), 2);

        if (abs($suma - $saldoPendiente) > 0.02) {
            throw ValidationException::withMessages([
                'cuotas_plan' => 'La suma de los pagos (Bs. '.number_format($suma, 2, '.', '').') debe igualar el saldo pendiente (Bs. '.number_format($saldoPendiente, 2, '.', '').').',
            ]);
        }

        $inicial = $cuotasPlan[0];
        $restantes = array_slice($cuotasPlan, 1);

        return DB::transaction(function () use ($data, $inicial, $restantes) {
            $pagoInmediato = $this->guardarPago(array_merge($data, [
                'tipo_pago' => 'contado',
                'monto' => (float) $inicial['monto'],
                'fecha_pago' => $inicial['fecha'] ?? now(),
                'concepto_pago' => count($restantes) > 0 ? 'anticipo' : 'completo',
            ]));

            if (count($restantes) === 0) {
                return $pagoInmediato;
            }

            $montoCredito = round(array_sum(array_column($restantes, 'monto')), 2);
            $pagoCredito = $this->guardarPago(array_merge($data, [
                'tipo_pago' => 'credito',
                'monto' => $montoCredito,
                'fecha_pago' => null,
                'id_transaccion_externa' => null,
                'concepto_pago' => 'saldo',
            ]));

            $this->cuotaCreditoService->crearCuotasPersonalizadas($restantes, $pagoCredito->id);

            return $pagoInmediato;
        });
    }

    private function crearPlanCredito(array $data): Pago
    {
        $pagoCredito = $this->guardarPago(array_merge($data, [
            'tipo_pago' => 'credito',
            'fecha_pago' => null,
            'id_transaccion_externa' => null,
        ]));

        $this->cuotaCreditoService->crearCuotas($data, $pagoCredito->id);

        return $pagoCredito;
    }

    private function esMetodoInmediato(string $metodo): bool
    {
        return in_array($metodo, ['efectivo', 'tarjeta', 'transferencia', 'qr'], true);
    }

    private function guardarPago(array $data): Pago
    {
        $pago = $this->repository->create($data);
        $this->registrarBitacora(
            'crear',
            'pagos',
            "Pago {$data['tipo_pago']} registrado: Bs. {$pago->monto}",
            null,
            $pago->toArray()
        );

        return $pago->load(['notaVenta.cliente', 'consulta.servicio', 'consulta.mascota', 'servicio', 'cliente', 'usuario']);
    }
}
