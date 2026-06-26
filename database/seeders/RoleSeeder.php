<?php

namespace Database\Seeders;

use App\Enums\PermisoEnum;
use App\Enums\RolEnum;
use App\Models\Usuarios\Permiso;
use App\Models\Usuarios\Rol;
use App\Support\PermisoBd;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RolEnum::values() as $nombreRol) {
            Rol::firstOrCreate(
                ['nombre' => $nombreRol],
                ['descripcion' => ucfirst($nombreRol)]
            );
        }

        $todosLosPermisos = Permiso::pluck('id')->all();

        $propietario = Rol::where('nombre', RolEnum::PROPIETARIO->value)->first();
        $propietario?->syncPermisos($todosLosPermisos);

        $administrador = Rol::where('nombre', RolEnum::ADMINISTRADOR->value)->first();
        $administrador?->syncPermisos($todosLosPermisos);

        $veterinario = Rol::where('nombre', RolEnum::VETERINARIO->value)->first();
        $veterinario?->syncPermisos($this->idsDesdeEnums([
            PermisoEnum::LISTAR_CLIENTES,
            PermisoEnum::VER_CLIENTES,
            PermisoEnum::CREAR_CLIENTES,
            PermisoEnum::EDITAR_CLIENTES,
            PermisoEnum::LISTAR_MASCOTAS,
            PermisoEnum::CREAR_MASCOTAS,
            PermisoEnum::EDITAR_MASCOTAS,
            PermisoEnum::VER_MASCOTAS,
            PermisoEnum::LISTAR_VETERINARIOS,
            PermisoEnum::GESTIONAR_VETERINARIOS,
            PermisoEnum::LISTAR_CONSULTAS_MEDICAS,
            PermisoEnum::CREAR_CONSULTAS_MEDICAS,
            PermisoEnum::EDITAR_CONSULTAS_MEDICAS,
            PermisoEnum::VER_CONSULTAS_MEDICAS,
            PermisoEnum::LISTAR_VACUNAS,
            PermisoEnum::CREAR_VACUNAS,
            PermisoEnum::EDITAR_VACUNAS,
            PermisoEnum::VER_VACUNAS,
            PermisoEnum::LISTAR_SERVICIOS,
            PermisoEnum::VER_SERVICIOS,
            PermisoEnum::LISTAR_PAGOS,
            PermisoEnum::CREAR_PAGOS,
            PermisoEnum::EDITAR_PAGOS,
            PermisoEnum::VER_PAGOS,
            PermisoEnum::LISTAR_BITACORA,
            PermisoEnum::VER_BITACORA,
        ]));

        $recepcionista = Rol::where('nombre', RolEnum::RECEPCIONISTA->value)->first();
        $recepcionista?->syncPermisos($this->idsDesdeEnums([
            PermisoEnum::LISTAR_CLIENTES,
            PermisoEnum::VER_CLIENTES,
            PermisoEnum::CREAR_CLIENTES,
            PermisoEnum::EDITAR_CLIENTES,
            PermisoEnum::LISTAR_MASCOTAS,
            PermisoEnum::CREAR_MASCOTAS,
            PermisoEnum::EDITAR_MASCOTAS,
            PermisoEnum::VER_MASCOTAS,
            PermisoEnum::LISTAR_VETERINARIOS,
            PermisoEnum::LISTAR_CONSULTAS_MEDICAS,
            PermisoEnum::VER_CONSULTAS_MEDICAS,
            PermisoEnum::EDITAR_CONSULTAS_MEDICAS,
            PermisoEnum::LISTAR_SERVICIOS,
            PermisoEnum::VER_SERVICIOS,
            PermisoEnum::LISTAR_PAGOS,
            PermisoEnum::CREAR_PAGOS,
            PermisoEnum::EDITAR_PAGOS,
            PermisoEnum::VER_PAGOS,
        ]));

        $cliente = Rol::where('nombre', RolEnum::CLIENTE->value)->first();
        $cliente?->syncPermisos($this->idsDesdeEnums([
            PermisoEnum::LISTAR_MASCOTAS,
            PermisoEnum::VER_MASCOTAS,
            PermisoEnum::LISTAR_CONSULTAS_MEDICAS,
            PermisoEnum::VER_CONSULTAS_MEDICAS,
            PermisoEnum::RESERVAR_CITAS,
            PermisoEnum::LISTAR_SERVICIOS,
            PermisoEnum::VER_SERVICIOS,
            PermisoEnum::VER_PAGOS,
        ]));
    }

    /**
     * @param  PermisoEnum[]  $casos
     */
    private function idsDesdeEnums(array $casos): array
    {
        $nombresBd = collect($casos)
            ->map(fn (PermisoEnum $permiso) => PermisoBd::resolver($permiso->value))
            ->unique()
            ->values();

        return Permiso::whereIn('nombre', $nombresBd)->pluck('id')->all();
    }
}
