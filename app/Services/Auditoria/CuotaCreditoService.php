<?php

namespace App\Services\Auditoria;

use App\Models\Auditoria\CuotaCredito;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CuotaCreditoService
{
    public function crearCuotas(array $data, int $pagoId): Collection
    {
        if (! empty($data['cuotas_plan']) && is_array($data['cuotas_plan'])) {
            return $this->crearCuotasPersonalizadas($data['cuotas_plan'], $pagoId, omitirInicial: true);
        }

        $numCuotas = (int) ($data['num_cuotas'] ?? 1);
        $montoTotal = (float) $data['monto'];
        $montoCuota = round($montoTotal / $numCuotas, 2);
        $fechaBase = Carbon::parse($data['fecha_pago'] ?? now());
        $cuotas = collect();

        for ($i = 1; $i <= $numCuotas; $i++) {
            $cuotas->push(CuotaCredito::create([
                'pago_id' => $pagoId,
                'usuario_id' => auth()->id(),
                'numero' => $i,
                'monto' => $i === $numCuotas
                    ? $montoTotal - ($montoCuota * ($numCuotas - 1))
                    : $montoCuota,
                'fecha_vencimiento' => $fechaBase->copy()->addMonths($i - 1),
                'estado' => 'pendiente',
            ]));
        }

        return $cuotas;
    }

    /**
     * @param  array<int, array{monto: float|int, fecha: string}>  $cuotasPlan
     */
    public function crearCuotasPersonalizadas(array $cuotasPlan, int $pagoId, bool $omitirInicial = false): Collection
    {
        $cuotas = $omitirInicial ? array_slice($cuotasPlan, 1) : $cuotasPlan;
        $resultado = collect();

        foreach (array_values($cuotas) as $indice => $cuota) {
            $resultado->push(CuotaCredito::create([
                'pago_id' => $pagoId,
                'usuario_id' => auth()->id(),
                'numero' => $indice + 1,
                'monto' => round((float) $cuota['monto'], 2),
                'fecha_vencimiento' => Carbon::parse($cuota['fecha']),
                'estado' => 'pendiente',
            ]));
        }

        return $resultado;
    }

    public function getPlanesActivos(): Collection
    {
        $pagoIdsActivos = CuotaCredito::query()
            ->where('estado', 'pendiente')
            ->distinct()
            ->pluck('pago_id');

        if ($pagoIdsActivos->isEmpty()) {
            return collect();
        }

        return CuotaCredito::query()
            ->with('pago:id,nota_venta_id')
            ->whereIn('pago_id', $pagoIdsActivos)
            ->orderBy('pago_id')
            ->orderBy('numero')
            ->get()
            ->groupBy('pago_id')
            ->map(function ($grupo, $pagoId) {
                return [
                    'id' => (int) $pagoId,
                    'pago_id' => (int) $pagoId,
                    'monto_total' => (float) $grupo->sum('monto'),
                    'num_cuotas' => $grupo->count(),
                    'nota_venta_id' => $grupo->first()->pago?->nota_venta_id,
                    'estado' => 'activo',
                    'cuotas' => $grupo->values(),
                ];
            })
            ->values();
    }

    public function registrarPagoCuota(int $cuotaId, array $data): CuotaCredito
    {
        $cuota = CuotaCredito::findOrFail($cuotaId);
        $cuota->update([
            'fecha_pago' => $data['fecha_pago'] ?? now(),
            'estado' => 'pagada',
            'metodo_pago' => $data['metodo_pago'] ?? null,
            'id_transaccion_externa' => $data['id_transaccion_externa'] ?? null,
        ]);

        return $cuota;
    }
}
