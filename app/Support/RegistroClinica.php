<?php

namespace App\Support;

use App\Models\Servicios\Cliente;
use App\Models\Servicios\ConsultaMedica;
use App\Models\Servicios\Mascota;

final class RegistroClinica
{
    public static function clienteIncompleto(?Cliente $cliente): bool
    {
        if (! $cliente) {
            return true;
        }

        $ci = trim((string) ($cliente->ci ?? ''));
        $telefono = trim((string) ($cliente->telefono ?? ''));

        // Reserva web: el teléfono se guarda como CI provisional
        if ($ci !== '' && $telefono !== '' && $ci === $telefono) {
            return true;
        }

        if (trim((string) ($cliente->apellido ?? '')) === '') {
            return true;
        }

        return false;
    }

    public static function mascotaIncompleta(?Mascota $mascota): bool
    {
        if (! $mascota) {
            return true;
        }

        return empty($mascota->raza_id);
    }

    public static function requiereRegistroEnLlegada(ConsultaMedica $consulta): bool
    {
        $consulta->loadMissing(['mascota.propietario']);

        return self::clienteIncompleto($consulta->mascota?->propietario)
            || self::mascotaIncompleta($consulta->mascota);
    }
}
