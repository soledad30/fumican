<?php

/**
 * Mapeo de tablas y configuración de la BD del grupo (PostgreSQL Tecnoweb).
 * El proyecto NO crea tablas de dominio: usa el esquema existente en db_grupo23sa.
 */
return [
    'usar_bd_grupo' => env('USAR_BD_GRUPO', true),

    'host' => env('DB_HOST', 'tecnoweb.org.bo'),
    'database' => env('DB_DATABASE', 'db_grupo23sa'),

    'tablas' => [
        'usuarios' => 'usuarios',
        'roles' => 'roles',
        'permisos' => 'permisos',
        'roles_permisos' => 'roles_permisos',
        'clientes' => 'clientes',
        'mascotas' => 'mascotas',
        'especies' => 'especies',
        'razas' => 'razas',
        'consultas_medicas' => 'consultas_medicas',
        'tratamientos' => 'tratamientos',
        'servicios' => 'servicios',
        'vacunas' => 'vacunas',
        'historial_vacunacion' => 'historial_vacunacion',
        'pagos' => 'pagos',
        'productos' => 'productos',
        'proveedores' => 'proveedores',
        'almacenes' => 'almacenes',
        'categorias' => 'categorias',
        'inventarios' => 'inventarios',
        'notas_compra' => 'notas_compra',
        'detalles_nota_compra' => 'detalles_nota_compra',
        'notas_venta' => 'notas_venta',
        'detalles_nota_venta' => 'detalles_nota_venta',
    ],
];
