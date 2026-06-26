<?php

namespace App\Support;

use App\Models\Servicios\Pago;
use App\Models\Ventas\NotaVenta;

class NotaVentaSaldo
{
    public static function montoPagado(NotaVenta $nota): float
    {
        $pagos = SaldoCobrado::sumPagosCobrados(
            Pago::query()->where('nota_venta_id', $nota->id)
        );
        $cuotas = SaldoCobrado::sumCuotasPagadasPorNota($nota->id);

        return round($pagos + $cuotas, 2);
    }

    public static function saldoPendiente(NotaVenta $nota): float
    {
        $total = (float) ($nota->monto_total ?? 0);

        return max(0, round($total - self::montoPagado($nota), 2));
    }
}
