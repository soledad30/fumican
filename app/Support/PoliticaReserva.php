<?php

namespace App\Support;

use App\Models\Servicios\ConsultaMedica;
use Carbon\Carbon;

final class PoliticaReserva
{
    public static function horaCierreHoy(): Carbon
    {
        $cierre = (string) config('reservas.hora_cierre_clinica', '19:00');

        return Carbon::parse(Carbon::today()->format('Y-m-d').' '.substr($cierre, 0, 5));
    }

    public static function fechaCita(ConsultaMedica $consulta): ?Carbon
    {
        if (! $consulta->fecha) {
            return null;
        }

        return Carbon::parse($consulta->fecha)->startOfDay();
    }

    public static function inicioCita(ConsultaMedica $consulta): ?Carbon
    {
        if (! $consulta->fecha) {
            return null;
        }

        $hora = $consulta->hora
            ? Carbon::parse($consulta->hora)->format('H:i:s')
            : '23:59:00';

        return Carbon::parse(Carbon::parse($consulta->fecha)->format('Y-m-d').' '.$hora);
    }

    public static function esHoy(ConsultaMedica $consulta): bool
    {
        $fecha = self::fechaCita($consulta);

        return $fecha && $fecha->eq(Carbon::today());
    }

    public static function horaCitaPasada(ConsultaMedica $consulta): bool
    {
        $inicio = self::inicioCita($consulta);

        return $inicio && $inicio->isPast();
    }

    /** El día de la cita ya cerró (día anterior o hoy después de hora de cierre). */
    public static function diaCitaTerminado(ConsultaMedica $consulta): bool
    {
        $fecha = self::fechaCita($consulta);
        if (! $fecha) {
            return false;
        }

        if ($fecha->lt(Carbon::today())) {
            return true;
        }

        if ($fecha->eq(Carbon::today())) {
            return now()->gte(self::horaCierreHoy());
        }

        return false;
    }

    public static function puedeReprogramarTarde(ConsultaMedica $consulta): bool
    {
        if ($consulta->estado !== 'reservada') {
            return false;
        }

        if ((bool) ($consulta->reprogramada_tarde ?? false)) {
            return false;
        }

        if (! self::esHoy($consulta) || ! self::horaCitaPasada($consulta)) {
            return false;
        }

        return ! self::diaCitaTerminado($consulta);
    }

    public static function puedeMarcarNoAsistio(ConsultaMedica $consulta): bool
    {
        return $consulta->estado === 'reservada' && self::diaCitaTerminado($consulta);
    }
}
