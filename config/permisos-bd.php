<?php

$catalogo = require __DIR__.'/permisos-catalogo.php';

$mapa = [];
foreach (array_keys($catalogo) as $nombreBd) {
    $mapa[str_replace('_', ' ', $nombreBd)] = $nombreBd;
}

return [
    /*
    | Mapeo permisos app (espacios) → permisos BD (snake_case).
    | Incluye alias en español natural usados en enums y formularios.
    */
    'mapa' => array_merge($mapa, [
        'listar consultas medicas' => 'listar_consultas',
        'crear consultas medicas' => 'crear_consultas',
        'editar consultas medicas' => 'editar_consultas',
        'ver consultas medicas' => 'ver_consultas',
        'eliminar consultas medicas' => 'eliminar_consultas',
        'reservar citas' => 'reservar_citas',

        'listar vacunas' => 'listar_historial',
        'crear vacunas' => 'crear_historial',
        'editar vacunas' => 'editar_historial',
        'ver vacunas' => 'ver_historial',
        'eliminar vacunas' => 'eliminar_historial',

        'listar medicamentos' => 'listar_productos',
        'crear medicamentos' => 'crear_productos',
        'editar medicamentos' => 'editar_productos',
        'ver medicamentos' => 'ver_productos',
        'eliminar medicamentos' => 'eliminar_productos',

        'listar notas de ventas' => 'listar_ventas',
        'crear notas de ventas' => 'crear_ventas',
        'editar notas de ventas' => 'editar_ventas',
        'ver notas de ventas' => 'ver_ventas',
        'eliminar notas de ventas' => 'eliminar_ventas',

        'listar notas de compras' => 'listar_compras',
        'crear notas de compras' => 'crear_compras',
        'editar notas de compras' => 'editar_compras',
        'ver notas de compras' => 'ver_compras',
        'eliminar notas de compras' => 'eliminar_compras',

        'listar almacenes' => 'listar_inventarios',
        'crear almacenes' => 'crear_inventarios',
        'editar almacenes' => 'editar_inventarios',
        'ver almacenes' => 'ver_inventarios',
        'eliminar almacenes' => 'eliminar_inventarios',

        'listar inventario' => 'listar_inventarios',
        'crear inventario' => 'crear_inventarios',
        'editar inventario' => 'editar_inventarios',
        'ver inventario' => 'ver_inventarios',
        'eliminar inventario' => 'eliminar_inventarios',

        'listar bitacora' => 'ver_reportes',
        'ver bitacora' => 'ver_reportes',

        'listar permisos' => 'administrar_sistema',
        'crear permisos' => 'administrar_sistema',
        'editar permisos' => 'administrar_sistema',
        'ver permisos' => 'administrar_sistema',
        'eliminar permisos' => 'administrar_sistema',

        'habilitar usuarios' => 'editar_usuarios',
        'deshabilitar usuarios' => 'editar_usuarios',

        'listar prompt' => 'administrar_sistema',
        'crear prompt' => 'administrar_sistema',
        'editar prompt' => 'administrar_sistema',
        'ver prompt' => 'administrar_sistema',
        'eliminar prompt' => 'administrar_sistema',
    ]),

    /*
    | Permisos "gestionar_*" que cubren acciones granulares (compatibilidad con roles existentes).
    */
    'paquetes' => [
        'gestionar_usuarios' => ['crear_usuarios', 'editar_usuarios'],
        'gestionar_mascotas' => ['crear_mascotas', 'editar_mascotas', 'eliminar_mascotas'],
        'gestionar_veterinarios' => ['crear_veterinarios', 'editar_veterinarios', 'eliminar_veterinarios'],
        'gestionar_consultas' => ['crear_consultas', 'editar_consultas', 'eliminar_consultas'],
        'gestionar_historial' => ['crear_historial', 'editar_historial', 'eliminar_historial'],
        'gestionar_servicios' => ['crear_servicios', 'editar_servicios', 'eliminar_servicios'],
        'gestionar_pagos' => ['crear_pagos', 'editar_pagos', 'eliminar_pagos'],
        'gestionar_productos' => ['crear_productos', 'editar_productos', 'eliminar_productos', 'crear_categorias', 'editar_categorias', 'eliminar_categorias'],
        'gestionar_inventarios' => ['crear_inventarios', 'editar_inventarios', 'eliminar_inventarios'],
        'gestionar_ventas' => ['crear_ventas', 'editar_ventas', 'eliminar_ventas'],
        'gestionar_compras' => ['crear_compras', 'editar_compras', 'eliminar_compras', 'crear_proveedores', 'editar_proveedores', 'eliminar_proveedores'],
    ],

    'admin' => 'administrar_sistema',
];
