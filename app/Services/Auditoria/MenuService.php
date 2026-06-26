<?php

namespace App\Services\Auditoria;

use App\Models\Auditoria\MenuDinamico;
use App\Support\MenuConfig;

class MenuService
{
    public function treeFor($user): array
    {
        return MenuConfig::treeFor($user);
    }

    private function permite($user, ?string $permisoBd): bool
    {
        if ($permisoBd === null) {
            return true;
        }

        return $user->tienePermisoBd(config('permisos-bd.admin'))
            || $user->tienePermisoBd($permisoBd);
    }

    private function sembrarMenusPorDefecto(): void
    {
        $definicion = [
            ['nombre' => 'Dashboard', 'icono' => 'fa-solid fa-house', 'enlace' => '/dashboard', 'permiso_bd' => null, 'orden' => 1, 'hijos' => []],
            ['nombre' => 'Usuarios', 'icono' => 'fa-solid fa-users', 'enlace' => null, 'permiso_bd' => 'administrar_sistema', 'orden' => 2, 'hijos' => [
                ['nombre' => 'Personal', 'enlace' => '/usuarios/personal', 'permiso_bd' => 'administrar_sistema'],
                ['nombre' => 'Roles', 'enlace' => '/usuarios/roles', 'permiso_bd' => 'administrar_sistema'],
            ]],
            ['nombre' => 'Servicios', 'icono' => 'fa-solid fa-briefcase', 'enlace' => null, 'permiso_bd' => null, 'orden' => 3, 'hijos' => [
                ['nombre' => 'Clientes', 'enlace' => '/servicios/clientes', 'permiso_bd' => 'listar_clientes'],
                ['nombre' => 'Consultas médicas', 'enlace' => '/servicios/consultas-medicas', 'permiso_bd' => 'listar_consultas'],
                ['nombre' => 'Mascotas', 'enlace' => '/servicios/mascotas', 'permiso_bd' => 'listar_mascotas'],
                ['nombre' => 'Especies', 'enlace' => '/servicios/especies', 'permiso_bd' => 'listar_mascotas'],
                ['nombre' => 'Razas', 'enlace' => '/servicios/razas', 'permiso_bd' => 'listar_mascotas'],
                ['nombre' => 'Catálogo de servicios', 'enlace' => '/servicios/catalogo', 'permiso_bd' => 'listar_servicios'],
                ['nombre' => 'Vacunas', 'enlace' => '/servicios/vacunas', 'permiso_bd' => 'listar_historial'],
                ['nombre' => 'Pagos', 'enlace' => '/servicios/pagos', 'permiso_bd' => 'listar_pagos'],
            ]],
            ['nombre' => 'Ventas', 'icono' => 'fa-solid fa-money-bill', 'enlace' => null, 'permiso_bd' => null, 'orden' => 4, 'hijos' => [
                ['nombre' => 'Notas de ventas', 'enlace' => '/ventas/notas-venta', 'permiso_bd' => 'listar_ventas'],
                ['nombre' => 'Notas de compras', 'enlace' => '/ventas/notas-compra', 'permiso_bd' => 'listar_compras'],
                ['nombre' => 'Almacén', 'enlace' => '/ventas/almacenes', 'permiso_bd' => 'listar_inventarios'],
                ['nombre' => 'Productos', 'enlace' => '/ventas/productos', 'permiso_bd' => 'listar_productos'],
                ['nombre' => 'Categorías', 'enlace' => '/ventas/categorias', 'permiso_bd' => 'listar_productos'],
                ['nombre' => 'Proveedores', 'enlace' => '/ventas/proveedores', 'permiso_bd' => 'listar_compras'],
            ]],
            ['nombre' => 'Reportes', 'icono' => 'fa-solid fa-chart-line', 'enlace' => null, 'permiso_bd' => null, 'orden' => 5, 'hijos' => [
                ['nombre' => 'Estadísticas', 'enlace' => '/reportes', 'permiso_bd' => null],
                ['nombre' => 'Bitácora', 'enlace' => '/reportes/bitacora', 'permiso_bd' => 'ver_reportes'],
                ['nombre' => 'Matriz de acceso', 'enlace' => '/reportes/matriz-acceso', 'permiso_bd' => 'administrar_sistema'],
            ]],
            ['nombre' => 'Tecnología', 'icono' => 'fa-solid fa-laptop', 'enlace' => null, 'permiso_bd' => 'administrar_sistema', 'orden' => 6, 'hijos' => [
                ['nombre' => 'Configuración', 'enlace' => '/calidad/prompt', 'permiso_bd' => 'administrar_sistema'],
            ]],
        ];

        foreach ($definicion as $item) {
            $padre = MenuDinamico::create([
                'nombre' => $item['nombre'],
                'icono' => $item['icono'],
                'enlace' => $item['enlace'],
                'permiso_bd' => $item['permiso_bd'],
                'orden' => $item['orden'],
            ]);

            foreach ($item['hijos'] as $i => $hijo) {
                MenuDinamico::create([
                    'nombre' => $hijo['nombre'],
                    'enlace' => $hijo['enlace'],
                    'permiso_bd' => $hijo['permiso_bd'],
                    'parent_id' => $padre->id,
                    'orden' => $i + 1,
                ]);
            }
        }
    }
}
