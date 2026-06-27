<?php

namespace Database\Seeders;

use App\Models\Usuarios\Permiso;
use Illuminate\Database\Seeder;

/**
 * Solo para entornos locales (USAR_BD_GRUPO=false).
 * En producción los permisos vienen de db_grupo23sa.
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('permisos-catalogo', []) as $nombre => $descripcion) {
            $permiso = Permiso::firstOrNew(['nombre' => $nombre]);
            $permiso->descripcion = $descripcion;
            $permiso->timestamps = false;
            $permiso->save();
        }
    }
}
