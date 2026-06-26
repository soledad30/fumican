<?php

namespace App\Support;

use App\Models\Auditoria\CuotaPago;
use App\Models\Servicios\Pago;
use Illuminate\Database\Eloquent\Builder;

class SaldoCobrado
{
    public static function sumPagosCobrados(Builder $query): float
    {
        return (float) (clone $query)
            ->where(function (Builder $q) {
                $q->where('tipo_pago', 'contado')
                    ->orWhereNotNull('fecha_pago');
            })
            ->sum('monto');
    }

    public static function sumCuotasPagadasPorNota(int $notaVentaId): float
    {
        return (float) CuotaPago::query()
            ->where('estado', 'pagada')
            ->whereHas('plan', fn (Builder $p) => $p->where('nota_venta_id', $notaVentaId))
            ->sum('monto');
    }

    public static function sumCuotasPagadasPorConsulta(int $consultaId): float
    {
        $pagoIds = Pago::query()->where('consulta_id', $consultaId)->pluck('id');

        if ($pagoIds->isEmpty()) {
            return 0.0;
        }

        return (float) CuotaPago::query()
            ->where('estado', 'pagada')
            ->whereHas('plan', fn (Builder $p) => $p->whereIn('pago_id', $pagoIds))
            ->sum('monto');
    }
}
