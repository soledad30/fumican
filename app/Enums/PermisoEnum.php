<?php

namespace App\Enums;

enum PermisoEnum: string
{
    case LISTAR_USUARIOS = 'listar usuarios';
    case CREAR_USUARIOS = 'crear usuarios';
    case EDITAR_USUARIOS = 'editar usuarios';
    case VER_USUARIOS = 'ver usuarios';
    case HABILITAR_USUARIOS = 'habilitar usuarios';
    case DESHABILITAR_USUARIOS = 'deshabilitar usuarios';

    case LISTAR_ROLES = 'listar roles';
    case CREAR_ROLES = 'crear roles';
    case EDITAR_ROLES = 'editar roles';
    case VER_ROLES = 'ver roles';
    case ELIMINAR_ROLES = 'eliminar roles';

    case LISTAR_PERMISOS = 'listar permisos';
    case CREAR_PERMISOS = 'crear permisos';
    case EDITAR_PERMISOS = 'editar permisos';
    case VER_PERMISOS = 'ver permisos';
    case ELIMINAR_PERMISOS = 'eliminar permisos';

    case LISTAR_CLIENTES = 'listar clientes';
    case CREAR_CLIENTES = 'crear clientes';
    case EDITAR_CLIENTES = 'editar clientes';
    case VER_CLIENTES = 'ver clientes';
    case ELIMINAR_CLIENTES = 'eliminar clientes';

    case LISTAR_CONSULTAS_MEDICAS = 'listar consultas medicas';
    case CREAR_CONSULTAS_MEDICAS = 'crear consultas medicas';
    case EDITAR_CONSULTAS_MEDICAS = 'editar consultas medicas';
    case VER_CONSULTAS_MEDICAS = 'ver consultas medicas';
    case ELIMINAR_CONSULTAS_MEDICAS = 'eliminar consultas medicas';
    case RESERVAR_CITAS = 'reservar citas';

    case LISTAR_MASCOTAS = 'listar mascotas';
    case CREAR_MASCOTAS = 'crear mascotas';
    case EDITAR_MASCOTAS = 'editar mascotas';
    case VER_MASCOTAS = 'ver mascotas';
    case ELIMINAR_MASCOTAS = 'eliminar mascotas';

    case LISTAR_ESPECIES = 'listar especies';
    case CREAR_ESPECIES = 'crear especies';
    case EDITAR_ESPECIES = 'editar especies';
    case VER_ESPECIES = 'ver especies';
    case ELIMINAR_ESPECIES = 'eliminar especies';

    case LISTAR_RAZAS = 'listar razas';
    case CREAR_RAZAS = 'crear razas';
    case EDITAR_RAZAS = 'editar razas';
    case VER_RAZAS = 'ver razas';
    case ELIMINAR_RAZAS = 'eliminar razas';

    case LISTAR_VETERINARIOS = 'listar veterinarios';
    case CREAR_VETERINARIOS = 'crear veterinarios';
    case EDITAR_VETERINARIOS = 'editar veterinarios';
    case VER_VETERINARIOS = 'ver veterinarios';
    case ELIMINAR_VETERINARIOS = 'eliminar veterinarios';
    case GESTIONAR_VETERINARIOS = 'gestionar veterinarios';

    case LISTAR_VACUNAS = 'listar vacunas';
    case CREAR_VACUNAS = 'crear vacunas';
    case EDITAR_VACUNAS = 'editar vacunas';
    case VER_VACUNAS = 'ver vacunas';
    case ELIMINAR_VACUNAS = 'eliminar vacunas';

    case LISTAR_SERVICIOS = 'listar servicios';
    case CREAR_SERVICIOS = 'crear servicios';
    case EDITAR_SERVICIOS = 'editar servicios';
    case VER_SERVICIOS = 'ver servicios';
    case ELIMINAR_SERVICIOS = 'eliminar servicios';

    case LISTAR_PAGOS = 'listar pagos';
    case CREAR_PAGOS = 'crear pagos';
    case EDITAR_PAGOS = 'editar pagos';
    case VER_PAGOS = 'ver pagos';
    case ELIMINAR_PAGOS = 'eliminar pagos';

    case LISTAR_BITACORA = 'listar bitacora';
    case VER_BITACORA = 'ver bitacora';

    case LISTAR_ALMACENES = 'listar almacenes';
    case CREAR_ALMACENES = 'crear almacenes';
    case EDITAR_ALMACENES = 'editar almacenes';
    case VER_ALMACENES = 'ver almacenes';
    case ELIMINAR_ALMACENES = 'eliminar almacenes';

    case LISTAR_MEDICAMENTOS = 'listar medicamentos';
    case CREAR_MEDICAMENTOS = 'crear medicamentos';
    case EDITAR_MEDICAMENTOS = 'editar medicamentos';
    case VER_MEDICAMENTOS = 'ver medicamentos';
    case ELIMINAR_MEDICAMENTOS = 'eliminar medicamentos';

    case LISTAR_PROVEEDORES = 'listar proveedores';
    case CREAR_PROVEEDORES = 'crear proveedores';
    case EDITAR_PROVEEDORES = 'editar proveedores';
    case VER_PROVEEDORES = 'ver proveedores';
    case ELIMINAR_PROVEEDORES = 'eliminar proveedores';

    case LISTAR_INVENTARIO = 'listar inventario';
    case CREAR_INVENTARIO = 'crear inventario';
    case EDITAR_INVENTARIO = 'editar inventario';
    case VER_INVENTARIO = 'ver inventario';
    case ELIMINAR_INVENTARIO = 'eliminar inventario';

    case LISTAR_NOTAS_DE_VENTAS = 'listar notas de ventas';
    case CREAR_NOTAS_DE_VENTAS = 'crear notas de ventas';
    case EDITAR_NOTAS_DE_VENTAS = 'editar notas de ventas';
    case VER_NOTAS_DE_VENTAS = 'ver notas de ventas';
    case ELIMINAR_NOTAS_DE_VENTAS = 'eliminar notas de ventas';

    case LISTAR_NOTAS_DE_COMPRAS = 'listar notas de compras';
    case CREAR_NOTAS_DE_COMPRAS = 'crear notas de compras';
    case EDITAR_NOTAS_DE_COMPRAS = 'editar notas de compras';
    case VER_NOTAS_DE_COMPRAS = 'ver notas de compras';
    case ELIMINAR_NOTAS_DE_COMPRAS = 'eliminar notas de compras';

    case LISTAR_CATEGORIAS = 'listar categorias';
    case CREAR_CATEGORIAS = 'crear categorias';
    case EDITAR_CATEGORIAS = 'editar categorias';
    case VER_CATEGORIAS = 'ver categorias';
    case ELIMINAR_CATEGORIAS = 'eliminar categorias';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
