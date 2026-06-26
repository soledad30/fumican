<?php

namespace App\Enums;

enum UnidadMedidaEnum: string
{
    case UNIDAD = 'unidad';
    case COMPRIMIDO = 'comprimido';
    case CAPSULA = 'capsula';
    case ML = 'ml';
    case FRASCO = 'frasco';
    case PIPETA = 'pipeta';
    case SOBRE = 'sobre';
    case AMPOLLA = 'ampolla';
    case TABLETA = 'tableta';
    case CAJA = 'caja';
    case BOLSA = 'bolsa';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return [
            self::UNIDAD->value => 'Unidad',
            self::COMPRIMIDO->value => 'Comprimido',
            self::CAPSULA->value => 'Cápsula',
            self::ML->value => 'Mililitro (ml)',
            self::FRASCO->value => 'Frasco',
            self::PIPETA->value => 'Pipeta',
            self::SOBRE->value => 'Sobre',
            self::AMPOLLA->value => 'Ampolla',
            self::TABLETA->value => 'Tableta masticable',
            self::CAJA->value => 'Caja',
            self::BOLSA->value => 'Bolsa',
        ];
    }
}
