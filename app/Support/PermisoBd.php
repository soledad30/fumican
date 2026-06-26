<?php

namespace App\Support;

class PermisoBd
{
    public static function resolver(string $permisoApp): string
    {
        $mapa = config('permisos-bd.mapa', []);

        if (isset($mapa[$permisoApp])) {
            return $mapa[$permisoApp];
        }

        return str_replace(' ', '_', $permisoApp);
    }

    public static function esAdmin(string $nombreBd): bool
    {
        return $nombreBd === config('permisos-bd.admin');
    }
}
