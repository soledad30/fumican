<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * La BD del grupo (db_grupo23sa) ya viene poblada.
     * No ejecutar seeders de dominio contra PostgreSQL de Tecnoweb.
     */
    public function run(): void
    {
        if (config('tablas.usar_bd_grupo', true)) {
            $this->command?->warn('Seeders omitidos: el proyecto usa la BD existente db_grupo23sa.');

            return;
        }

        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            LocalDemoSeeder::class,
        ]);
    }
}
