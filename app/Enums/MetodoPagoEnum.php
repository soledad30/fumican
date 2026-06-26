<?php

namespace App\Enums;

enum MetodoPagoEnum: string
{
    case EFECTIVO = 'efectivo';
    case TARJETA = 'tarjeta';
    case TRANSFERENCIA = 'transferencia';
    case QR = 'qr';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return [
            self::EFECTIVO->value => 'Efectivo',
            self::TARJETA->value => 'Tarjeta',
            self::TRANSFERENCIA->value => 'Transferencia',
            self::QR->value => 'QR / TigoMoney',
        ];
    }
}
