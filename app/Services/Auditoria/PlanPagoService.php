<?php

namespace App\Services\Auditoria;

use App\Models\Auditoria\CuotaPago;
use App\Models\Auditoria\PlanPago;
use Carbon\Carbon;

class PlanPagoService
{
    public function crearPlan(array $data, int $pagoId): PlanPago
    {
        $numCuotas = (int) ($data['num_cuotas'] ?? 1);
        $montoTotal = (float) $data['monto'];

        $plan = PlanPago::create([
            'pago_id' => $pagoId,
            'usuario_id' => auth()->id(),
            'nota_venta_id' => $data['nota_venta_id'] ?? null,
            'monto_total' => $montoTotal,
            'num_cuotas' => $numCuotas,
            'estado' => 'activo',
        ]);

        $montoCuota = round($montoTotal / $numCuotas, 2);
        $fechaBase = Carbon::parse($data['fecha_pago'] ?? now());

        for ($i = 1; $i <= $numCuotas; $i++) {
            CuotaPago::create([
                'plan_pago_id' => $plan->id,
                'numero' => $i,
                'monto' => $i === $numCuotas
                    ? $montoTotal - ($montoCuota * ($numCuotas - 1))
                    : $montoCuota,
                'fecha_vencimiento' => $fechaBase->copy()->addMonths($i - 1),
                'estado' => $i === 1 && ($data['tipo_pago'] ?? '') === 'credito' ? 'pendiente' : 'pendiente',
            ]);
        }

        return $plan->load('cuotas');
    }

    public function getPlanesActivos()
    {
        return PlanPago::with('cuotas')->where('estado', 'activo')->orderByDesc('created_at')->get();
    }

    public function registrarPagoCuota(int $cuotaId, array $data): CuotaPago
    {
        $cuota = CuotaPago::findOrFail($cuotaId);
        $cuota->update([
            'fecha_pago' => $data['fecha_pago'] ?? now(),
            'estado' => 'pagada',
            'metodo_pago' => $data['metodo_pago'] ?? null,
            'id_transaccion_externa' => $data['id_transaccion_externa'] ?? null,
        ]);

        $plan = $cuota->plan;
        if ($plan->cuotas()->where('estado', 'pendiente')->count() === 0) {
            $plan->update(['estado' => 'completado']);
        }

        return $cuota;
    }
}
