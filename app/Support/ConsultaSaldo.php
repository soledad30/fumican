<?php

namespace App\Support;

use App\Models\Servicios\ConsultaMedica;
use App\Models\Servicios\Pago;

class ConsultaSaldo
{
    public static function montoPagado(ConsultaMedica $consulta): float
    {
        $pagos = SaldoCobrado::sumPagosCobrados(
            Pago::query()->where('consulta_id', $consulta->id)
        );
        $cuotas = SaldoCobrado::sumCuotasPagadasPorConsulta($consulta->id);

        return round($pagos + $cuotas, 2);
    }

    public static function saldoPendiente(ConsultaMedica $consulta): float
    {
        $total = (float) ($consulta->costo_consulta ?? 0);

        return max(0, round($total - self::montoPagado($consulta), 2));
    }

    public static function calcularAnticipo(float $precioServicio): float
    {
        $porcentaje = config('reservas.porcentaje_anticipo', 20);
        $monto = round($precioServicio * $porcentaje / 100, 2);

        return max($monto, 0.01);
    }
}
