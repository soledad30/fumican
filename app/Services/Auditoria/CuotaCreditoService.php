<?php

namespace App\Services\Auditoria;

use App\Models\Auditoria\CuotaCredito;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

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
                $pendientes = $grupo->where('estado', 'pendiente');

                return [
                    'id' => (int) $pagoId,
                    'pago_id' => (int) $pagoId,
                    'monto_total' => (float) $grupo->sum('monto'),
                    'saldo_pendiente' => (float) $pendientes->sum('monto'),
                    'num_cuotas' => $grupo->count(),
                    'cuotas_pendientes' => $pendientes->count(),
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

        if ($cuota->estado !== 'pendiente') {
            throw new InvalidArgumentException('Esta cuota ya fue pagada.');
        }

        $montoCuota = round((float) $cuota->monto, 2);
        $montoPagado = isset($data['monto'])
            ? round((float) $data['monto'], 2)
            : $montoCuota;

        if ($montoPagado <= 0) {
            throw new InvalidArgumentException('Indique un monto válido para el pago.');
        }

        if ($montoPagado > $montoCuota + 0.01) {
            throw new InvalidArgumentException('El monto no puede superar el saldo de la cuota (Bs. '.number_format($montoCuota, 2).').');
        }

        $fechaPago = $data['fecha_pago'] ?? now();
        $metodo = $data['metodo_pago'] ?? null;
        $transaccion = $data['id_transaccion_externa'] ?? null;

        if ($montoPagado >= $montoCuota - 0.01) {
            $cuota->update([
                'fecha_pago' => $fechaPago,
                'estado' => 'pagada',
                'metodo_pago' => $metodo,
                'id_transaccion_externa' => $transaccion,
            ]);

            return $cuota->fresh();
        }

        $restante = round($montoCuota - $montoPagado, 2);
        $cuota->update(['monto' => $restante]);

        return $cuota->fresh();
    }
}
