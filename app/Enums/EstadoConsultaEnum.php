<?php

namespace App\Enums;

enum EstadoConsultaEnum: string
{
    case RESERVADA = 'reservada';
    case EN_ESPERA = 'en_espera';
    case EN_ATENCION = 'en_atencion';
    case COMPLETADA = 'completada';
    case CANCELADA = 'cancelada';
    case NO_ASISTIO = 'no_asistio';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return [
            self::RESERVADA->value => 'Reservada',
            self::EN_ESPERA->value => 'En espera',
            self::EN_ATENCION->value => 'En atención',
            self::COMPLETADA->value => 'Completada',
            self::CANCELADA->value => 'Cancelada',
            self::NO_ASISTIO->value => 'No asistió',
        ];
    }

    public function transicionesPermitidas(): array
    {
        return match ($this) {
            self::RESERVADA => [
                self::EN_ESPERA,
                self::EN_ATENCION,
                self::COMPLETADA,
                self::CANCELADA,
                self::NO_ASISTIO,
            ],
            self::EN_ESPERA => [self::EN_ATENCION, self::CANCELADA],
            self::EN_ATENCION => [self::COMPLETADA, self::CANCELADA],
            default => [],
        };
    }

    public function puedeTransicionarA(self $destino): bool
    {
        return in_array($destino, $this->transicionesPermitidas(), true);
    }
}
