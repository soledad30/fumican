<?php

namespace App\Support;

use App\Enums\PermisoEnum;
use App\Enums\RolEnum;
use App\Models\Usuarios\Permiso;
use App\Models\Usuarios\Rol;

class RolCliente
{
    public static function resolver(): Rol
    {
        $rol = Rol::firstOrCreate(
            ['nombre' => RolEnum::CLIENTE->value],
            ['descripcion' => 'Cliente del portal']
        );

        if ($rol->permisos()->count() === 0) {
            $rol->syncPermisos(self::permisoIds());
        }

        return $rol;
    }

    public static function id(): int
    {
        return (int) self::resolver()->id;
    }

    /**
     * @return array<int, int>
     */
    private static function permisoIds(): array
    {
        $nombresBd = collect([
            PermisoEnum::LISTAR_MASCOTAS,
            PermisoEnum::VER_MASCOTAS,
            PermisoEnum::LISTAR_CONSULTAS_MEDICAS,
            PermisoEnum::VER_CONSULTAS_MEDICAS,
            PermisoEnum::RESERVAR_CITAS,
            PermisoEnum::LISTAR_SERVICIOS,
            PermisoEnum::VER_SERVICIOS,
            PermisoEnum::VER_PAGOS,
        ])->map(fn (PermisoEnum $permiso) => PermisoBd::resolver($permiso->value))
            ->unique()
            ->values();

        return Permiso::whereIn('nombre', $nombresBd)->pluck('id')->all();
    }
}
