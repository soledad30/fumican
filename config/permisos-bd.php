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
        'ver consultas medicas' => 'listar_consultas',
        'eliminar consultas medicas' => 'eliminar_consultas',
        'reservar citas' => 'reservar_citas',

        'listar vacunas' => 'listar_historial',
        'crear vacunas' => 'gestionar_historial',
        'editar vacunas' => 'gestionar_historial',
        'ver vacunas' => 'listar_historial',
        'eliminar vacunas' => 'gestionar_historial',

        'listar medicamentos' => 'listar_productos',
        'crear medicamentos' => 'gestionar_productos',
        'editar medicamentos' => 'gestionar_productos',
        'ver medicamentos' => 'listar_productos',
        'eliminar medicamentos' => 'gestionar_productos',

        'listar categorias' => 'listar_productos',
        'crear categorias' => 'gestionar_productos',
        'editar categorias' => 'gestionar_productos',
        'ver categorias' => 'listar_productos',
        'eliminar categorias' => 'gestionar_productos',

        'listar proveedores' => 'listar_compras',
        'crear proveedores' => 'gestionar_compras',
        'editar proveedores' => 'gestionar_compras',
        'ver proveedores' => 'listar_compras',
        'eliminar proveedores' => 'gestionar_compras',

        'listar notas de ventas' => 'listar_ventas',
        'crear notas de ventas' => 'gestionar_ventas',
        'editar notas de ventas' => 'gestionar_ventas',
        'ver notas de ventas' => 'listar_ventas',
        'eliminar notas de ventas' => 'gestionar_ventas',

        'listar notas de compras' => 'listar_compras',
        'crear notas de compras' => 'gestionar_compras',
        'editar notas de compras' => 'gestionar_compras',
        'ver notas de compras' => 'listar_compras',
        'eliminar notas de compras' => 'gestionar_compras',

        'listar almacenes' => 'listar_inventarios',
        'crear almacenes' => 'gestionar_inventarios',
        'editar almacenes' => 'gestionar_inventarios',
        'ver almacenes' => 'listar_inventarios',
        'eliminar almacenes' => 'gestionar_inventarios',

        'listar inventario' => 'listar_inventarios',
        'crear inventario' => 'gestionar_inventarios',
        'editar inventario' => 'gestionar_inventarios',
        'ver inventario' => 'listar_inventarios',
        'eliminar inventario' => 'gestionar_inventarios',

        'listar bitacora' => 'ver_reportes',
        'ver bitacora' => 'ver_reportes',

        'listar permisos' => 'administrar_sistema',
        'crear permisos' => 'administrar_sistema',
        'editar permisos' => 'administrar_sistema',
        'ver permisos' => 'administrar_sistema',
        'eliminar permisos' => 'administrar_sistema',

        'habilitar usuarios' => 'editar_usuarios',
        'deshabilitar usuarios' => 'editar_usuarios',

        'crear pagos' => 'gestionar_pagos',
        'editar pagos' => 'gestionar_pagos',
        'eliminar pagos' => 'gestionar_pagos',
        'ver pagos' => 'listar_pagos',

        'listar prompt' => 'administrar_sistema',
        'crear prompt' => 'administrar_sistema',
        'editar prompt' => 'administrar_sistema',
        'ver prompt' => 'administrar_sistema',
        'eliminar prompt' => 'administrar_sistema',
    ]),

    'admin' => 'administrar_sistema',
];
