<?php

namespace App\Enums;

enum TipoPagoEnum: string
{
    case CONTADO = 'contado';
    case CREDITO = 'credito';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return [
            self::CONTADO->value => 'Contado',
            self::CREDITO->value => 'Crédito',
        ];
    }
}
