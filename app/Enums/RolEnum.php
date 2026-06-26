<?php

namespace App\Enums;

enum RolEnum: string
{
    case PROPIETARIO = 'propietario';
    case ADMINISTRADOR = 'administrador';
    case VETERINARIO = 'veterinario';
    case RECEPCIONISTA = 'recepcionista';
    case CLIENTE = 'cliente';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
