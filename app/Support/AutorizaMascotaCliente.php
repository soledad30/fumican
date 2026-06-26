<?php

namespace App\Support;

use App\Enums\PermisoEnum;
use App\Enums\RolEnum;
use App\Models\Servicios\Cliente;
use App\Models\Servicios\Mascota;
use App\Models\Usuario;
use App\Support\PermisoBd;

class AutorizaMascotaCliente
{
    public static function puedeVer(Usuario $user, Mascota $mascota): bool
    {
        if ($user->esRolConAccesoTotal()) {
            return true;
        }

        if ($user->tienePermisoBd(PermisoBd::resolver(PermisoEnum::LISTAR_MASCOTAS->value))) {
            return true;
        }

        if ($user->rol?->nombre !== RolEnum::CLIENTE->value) {
            return false;
        }

        $cliente = Cliente::query()->where('usuario_id', $user->id)->first();

        return $cliente && (int) $mascota->cliente_id === (int) $cliente->id;
    }

    public static function clienteDeUsuario(Usuario $user): ?Cliente
    {
        return Cliente::query()
            ->where('usuario_id', $user->id)
            ->with(['mascotas.raza.especie'])
            ->first();
    }
}
