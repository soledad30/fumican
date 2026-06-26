<?php

namespace Database\Seeders;

use App\Enums\PermisoEnum;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('menus')->insert([
            [
                'name' => 'Dashboard',
                'icon' => 'fa-solid fa-house',
                'link' => '/dashboard',
                'parent_id' => null,
            ],
            [
                'name' => 'Usuarios',
                'icon' => 'fa-solid fa-users',
                'link' => null,
                'parent_id' => null,
            ],
            [
                'name' => 'Servicios',
                'icon' => 'fa-solid fa-briefcase',
                'link' => null,
                'parent_id' => null,
            ],
            [
                'name' => 'Ventas',
                'icon' => 'fa-solid fa-money-bill',
                'link' => null,
                'parent_id' => null,
            ],
            [
                'name' => 'Tecnologia',
                'icon' => 'fa-solid fa-laptop',
                'link' => null,
                'parent_id' => null,
            ],
        ]);
        DB::table('menus')->insert([
            [
                'name' => 'Personal',
                'icon' => null,
                'link' => '/usuarios/personal',
                'permission_id' => DB::table('permissions')->where('name', 'listar usuarios')->first()->id,
                'parent_id' => DB::table('menus')->where('name', 'Usuarios')->first()->id,
            ],
            [
                'name' => 'Roles',
                'icon' => null,
                'link' => '/usuarios/roles',
                'permission_id' => DB::table('permissions')->where('name', 'listar roles')->first()->id,
                'parent_id' => DB::table('menus')->where('name', 'Usuarios')->first()->id,
            ],
            // [
            //     'name' => 'Permisos',
            //     'icon' => null,
            //     'link' => '/users/permissions',
            //     'permission_id' => DB::table('permissions')->where('name', 'listar permisos')->first()->id,
            //     'parent_id' => DB::table('menus')->where('name', 'Usuarios')->first()->id,
            // ],
            // MODULO SERVICIOS
            [
                'name' => 'Clientes',
                'icon' => null,
                'link' => '/servicios/clientes',
                'permission_id' => DB::table('permissions')->where('name', 'listar clientes')->first()->id,
                'parent_id' => DB::table('menus')->where('name', 'Servicios')->first()->id,
            ],
            [
                'name' => 'Consultas médicas',
                'icon' => null,
                'link' => '/servicios/consultas-medicas',
                'permission_id' => DB::table('permissions')->where('name', 'listar consultas medicas')->first()->id,
                'parent_id' => DB::table('menus')->where('name', 'Servicios')->first()->id,
            ],
            [
                'name' => 'Mascotas',
                'icon' => null,
                'link' => '/servicios/mascotas',
                'permission_id' => DB::table('permissions')->where('name', 'listar mascotas')->first()->id,
                'parent_id' => DB::table('menus')->where('name', 'Servicios')->first()->id,
            ],
            [
                'name' => 'Catálogo de servicios',
                'icon' => null,
                'link' => '/servicios/catalogo',
                'permission_id' => DB::table('permissions')->where('name', 'listar servicios')->first()->id,
                'parent_id' => DB::table('menus')->where('name', 'Servicios')->first()->id,
            ],
            [
                'name' => 'Vacunas',
                'icon' => null,
                'link' => '/servicios/vacunas',
                'permission_id' => DB::table('permissions')->where('name', 'listar vacunas')->first()->id,
                'parent_id' => DB::table('menus')->where('name', 'Servicios')->first()->id,
            ],
            [
                'name' => 'Pagos',
                'icon' => null,
                'link' => '/servicios/pagos',
                'permission_id' => DB::table('permissions')->where('name', 'listar pagos')->first()->id,
                'parent_id' => DB::table('menus')->where('name', 'Servicios')->first()->id,
            ],
            // MODULO DE VENTAS
            [
                'name' => 'Notas de ventas',
                'icon' => null,
                'link' => '/ventas/notas-venta',
                'permission_id' => DB::table('permissions')->where('name', PermisoEnum::LISTAR_NOTAS_DE_VENTAS->value)->first()->id,
                'parent_id' => DB::table('menus')->where('name', 'Ventas')->first()->id,
            ],
            [
                'name' => 'Notas de compras',
                'icon' => null,
                'link' => '/ventas/notas-compra',
                'permission_id' => DB::table('permissions')->where('name', PermisoEnum::LISTAR_NOTAS_DE_COMPRAS->value)->first()->id,
                'parent_id' => DB::table('menus')->where('name', 'Ventas')->first()->id,
            ],
            [
                'name' => 'Almacen',
                'icon' => null,
                'link' => '/ventas/almacenes',
                'permission_id' => DB::table('permissions')->where('name', 'listar almacenes')->first()->id,
                'parent_id' => DB::table('menus')->where('name', 'Ventas')->first()->id,
            ],
            [
                'name' => 'Medicamentos',
                'icon' => null,
                'link' => '/ventas/productos',
                'permission_id' => DB::table('permissions')->where('name', 'listar medicamentos')->first()->id,
                'parent_id' => DB::table('menus')->where('name', 'Ventas')->first()->id,
            ],
            [
                'name' => 'Categorias',
                'icon' => null,
                'link' => '/ventas/categorias',
                'permission_id' => DB::table('permissions')->where('name', PermisoEnum::LISTAR_CATEGORIAS->value)->first()->id,
                'parent_id' => DB::table('menus')->where('name', 'Ventas')->first()->id,
            ],
            [
                'name' => 'Proveedores',
                'icon' => null,
                'link' => '/ventas/proveedores',
                'permission_id' => DB::table('permissions')->where('name', 'listar proveedores')->first()->id,
                'parent_id' => DB::table('menus')->where('name', 'Ventas')->first()->id,
            ],
            // MODULO TECNOLOGIA
            [
                'name' => 'Configuración',
                'icon' => null,
                'link' => '/calidad/prompt',
                'permission_id' => DB::table('permissions')->where('name', 'listar prompt')->first()->id,
                'parent_id' => DB::table('menus')->where('name', 'Tecnologia')->first()->id,
            ],
        ]);
    }
}
