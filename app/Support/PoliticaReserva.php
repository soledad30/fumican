<?php

namespace App\Support;

use App\Models\Servicios\ConsultaMedica;
use Carbon\Carbon;

final class PoliticaReserva
{
    public static function minutosGracia(): int
    {
        return max(0, (int) config('reservas.minutos_gracia_no_asistio', 20));
    }

    public static function normalizarHora(?string $hora): string
    {
        if (! $hora) {
            return '23:59:00';
        }

        $hora = trim($hora);

        if (preg_match('/^\d{2}:\d{2}$/', $hora)) {
            return $hora.':00';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $hora)) {
            return $hora;
        }

        return Carbon::parse($hora)->format('H:i:s');
    }

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

        $hora = self::normalizarHora($consulta->hora ? (string) $consulta->hora : null);

        return Carbon::parse(Carbon::parse($consulta->fecha)->format('Y-m-d').' '.$hora);
    }

    public static function finVentanaAsistencia(ConsultaMedica $consulta): ?Carbon
    {
        $inicio = self::inicioCita($consulta);

        return $inicio?->copy()->addMinutes(self::minutosGracia());
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

    /**
     * Tras la hora de la cita + minutos de gracia (o día anterior), sin check-in → no asistió.
     */
    public static function ventanaNoAsistioCumplida(ConsultaMedica $consulta): bool
    {
        $fecha = self::fechaCita($consulta);
        if (! $fecha) {
            return false;
        }

        if ($fecha->lt(Carbon::today())) {
            return true;
        }

        if ($fecha->gt(Carbon::today())) {
            return false;
        }

        $fin = self::finVentanaAsistencia($consulta);

        return $fin && now()->gte($fin);
    }

    /** El día de la cita ya cerró para reprogramación tardía. */
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
            $finVentana = self::finVentanaAsistencia($consulta);
            $cierre = self::horaCierreHoy();
            $limite = $finVentana && $finVentana->gt($cierre) ? $finVentana : $cierre;

            return now()->gte($limite);
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
        return $consulta->estado === 'reservada' && self::ventanaNoAsistioCumplida($consulta);
    }
}
