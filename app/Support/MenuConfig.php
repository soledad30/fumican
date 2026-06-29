<?php

namespace App\Support;

use App\Enums\RolEnum;

class MenuConfig
{
    public static function treeFor($user): array
    {
        if ($user->rol?->nombre === RolEnum::CLIENTE->value) {
            return [
                [
                    'name' => 'Mi cuenta',
                    'icon' => 'fa-solid fa-house',
                    'link' => url('/mi-cuenta'),
                    'submenus' => [],
                ],
            ];
        }

        $definicion = [
            [
                'name' => 'Dashboard',
                'icon' => 'fa-solid fa-table-cells',
                'link' => url('/dashboard'),
                'permission' => null,
                'children' => [],
            ],
            [
                'name' => 'Usuarios',
                'icon' => 'fa-solid fa-users',
                'link' => url('/usuarios/personal'),
                'permission' => 'administrar_sistema',
                'children' => [
                    ['name' => 'Personal', 'link' => url('/usuarios/personal'), 'permission' => 'administrar_sistema'],
                    ['name' => 'Roles', 'link' => url('/usuarios/roles'), 'permission' => 'administrar_sistema'],
                ],
            ],
            [
                'name' => 'Mascotas',
                'icon' => 'fa-solid fa-heart',
                'link' => url('/servicios/mascotas'),
                'permission' => 'listar_mascotas',
                'children' => [],
            ],
            [
                'name' => 'Productos',
                'icon' => 'fa-solid fa-pills',
                'link' => url('/ventas/productos'),
                'permission' => 'listar_productos',
                'children' => [],
            ],
            [
                'name' => 'Servicios',
                'icon' => 'fa-solid fa-stethoscope',
                'link' => url('/servicios/catalogo'),
                'permission' => 'listar_servicios',
                'children' => [],
            ],
            [
                'name' => 'Consultas',
                'icon' => 'fa-solid fa-calendar-days',
                'link' => url('/servicios/consultas-medicas/agenda'),
                'permission' => 'listar_consultas',
                'children' => [
                    ['name' => 'Agenda de hoy', 'link' => url('/servicios/consultas-medicas/agenda'), 'permission' => 'listar_consultas'],
                    ['name' => 'Todas las consultas', 'link' => url('/servicios/consultas-medicas'), 'permission' => 'listar_consultas'],
                ],
            ],
            [
                'name' => 'Historial',
                'icon' => 'fa-solid fa-file-medical',
                'link' => url('/servicios/vacunas'),
                'permission' => 'listar_historial',
                'children' => [],
            ],
            [
                'name' => 'Reportes',
                'icon' => 'fa-solid fa-chart-column',
                'link' => url('/reportes'),
                'permission' => 'ver_reportes',
                'children' => [
                    ['name' => 'Bitácora', 'link' => url('/reportes/bitacora'), 'permission' => 'ver_reportes'],
                    ['name' => 'Matriz de acceso', 'link' => url('/reportes/matriz-acceso'), 'permission' => 'administrar_sistema'],
                ],
            ],
            [
                'name' => 'Ventas',
                'icon' => 'fa-solid fa-money-bill-wave',
                'link' => null,
                'permission' => null,
                'children' => [
                    ['name' => 'Notas de venta', 'link' => url('/ventas/notas-venta'), 'permission' => 'listar_ventas'],
                    ['name' => 'Notas de compra', 'link' => url('/ventas/notas-compra'), 'permission' => 'listar_compras'],
                    ['name' => 'Almacén', 'link' => url('/ventas/almacenes'), 'permission' => 'listar_inventarios'],
                    ['name' => 'Proveedores', 'link' => url('/ventas/proveedores'), 'permission' => 'listar_compras'],
                ],
            ],
            [
                'name' => 'Clínica',
                'icon' => 'fa-solid fa-hospital',
                'link' => null,
                'permission' => null,
                'children' => [
                    ['name' => 'Clientes', 'link' => url('/servicios/clientes'), 'permission' => 'listar_clientes'],
                    ['name' => 'Recepción', 'link' => url('/servicios/recepcion'), 'permission' => 'crear_clientes'],
                    ['name' => 'Veterinarios', 'link' => url('/servicios/veterinarios'), 'permission' => 'listar_veterinarios'],
                    ['name' => 'Pagos', 'link' => url('/servicios/pagos'), 'permission' => 'listar_pagos'],
                    ['name' => 'Especies', 'link' => url('/servicios/especies'), 'permission' => 'listar_mascotas'],
                    ['name' => 'Razas', 'link' => url('/servicios/razas'), 'permission' => 'listar_mascotas'],
                ],
            ],
        ];

        return collect($definicion)
            ->map(function (array $menu) use ($user) {
                $hijos = collect($menu['children'] ?? [])
                    ->filter(fn (array $child) => self::permite($user, $child['permission'] ?? null))
                    ->values()
                    ->all();

                if ($menu['link'] === null && empty($hijos)) {
                    return null;
                }

                if ($menu['permission'] && ! self::permite($user, $menu['permission']) && empty($hijos)) {
                    return null;
                }

                if ($menu['link'] && $menu['permission'] && ! self::permite($user, $menu['permission'])) {
                    if (empty($hijos)) {
                        return null;
                    }
                    $menu['link'] = null;
                }

                return [
                    'name' => $menu['name'],
                    'icon' => $menu['icon'],
                    'link' => $menu['link'],
                    'submenus' => $hijos,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private static function permite($user, ?string $permisoBd): bool
    {
        if ($permisoBd === null) {
            return true;
        }

        if ($user->esRolConAccesoTotal()) {
            return true;
        }

        return $user->tienePermisoBd(config('permisos-bd.admin'))
            || $user->tienePermisoBd($permisoBd);
    }
}
