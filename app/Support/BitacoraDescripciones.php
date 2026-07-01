<?php

namespace App\Support;

use Illuminate\Http\Request;

class BitacoraDescripciones
{
    /** Rutas que ya registran bitácora detallada en servicios/controladores. */
    private const RUTAS_EXCLUIDAS = [
        'usuarios.store', 'usuarios.update', 'usuarios.destroy',
        'especies.store', 'especies.update', 'especies.destroy',
        'razas.store', 'razas.update', 'razas.destroy',
        'servicios.store', 'servicios.update', 'servicios.destroy',
        'vacunas.store', 'vacunas.update', 'vacunas.destroy',
        'vacunas.historial.store', 'vacunas.historial.update', 'vacunas.historial.destroy',
        'pagos.store', 'pagos.update', 'pagos.destroy', 'pagos.cuotas.pagar',
        'tratamientos.store', 'tratamientos.update', 'tratamientos.destroy',
    ];

    private const ETIQUETAS_ACCION = [
        'login_exitoso' => 'Inicio de sesión',
        'login_fallido' => 'Login fallido',
        'acceso' => 'Navegación',
        'navegar' => 'Navegación',
        'crear' => 'Creación',
        'editar' => 'Edición',
        'eliminar' => 'Eliminación',
        'baja' => 'Baja',
        'cambiar_estado' => 'Cambio de estado',
        'iniciar_atencion' => 'Iniciar atención',
        'check_in' => 'Registro de llegada',
        'registro_llegada' => 'Completar llegada',
        'reprogramar' => 'Reprogramación',
        'pagar_cuota' => 'Pago de cuota',
        'generar' => 'Generación',
    ];

    private const ETIQUETAS_MODULO = [
        'autenticacion' => 'Autenticación',
        'navegacion' => 'Navegación',
        'usuarios' => 'Usuarios',
        'roles' => 'Roles',
        'clientes' => 'Clientes',
        'mascotas' => 'Mascotas',
        'consultas_medicas' => 'Consultas médicas',
        'veterinarios' => 'Veterinarios',
        'especies' => 'Especies',
        'razas' => 'Razas',
        'servicios' => 'Catálogo de servicios',
        'vacunas' => 'Vacunas',
        'pagos' => 'Pagos',
        'tratamientos' => 'Tratamientos',
        'recepcion' => 'Recepción',
        'proveedores' => 'Proveedores',
        'categorias' => 'Categorías',
        'productos' => 'Productos',
        'almacenes' => 'Almacenes',
        'notas_compra' => 'Notas de compra',
        'notas_venta' => 'Notas de venta',
        'portal' => 'Portal cliente',
        'calidad' => 'Calidad',
        'reportes' => 'Reportes',
    ];

    private const PAGINAS = [
        'dashboard' => 'Dashboard',
        'welcome' => 'Inicio',
        'portal.index' => 'Portal del cliente',
        'usuarios.index' => 'Usuarios — Personal',
        'usuarios.search' => 'Usuarios — Búsqueda',
        'roles.index' => 'Usuarios — Roles',
        'recepcion.index' => 'Recepción',
        'clientes.index' => 'Clientes',
        'clientes.search' => 'Clientes — Búsqueda',
        'consultas-medicas.index' => 'Consultas médicas',
        'consultas-medicas.search' => 'Consultas médicas — Búsqueda',
        'consultas-medicas.agenda' => 'Agenda de consultas',
        'mascotas.index' => 'Mascotas',
        'mascotas.search' => 'Mascotas — Búsqueda',
        'mascotas.historial' => 'Historial de mascota',
        'especies.index' => 'Especies',
        'razas.index' => 'Razas',
        'veterinarios.index' => 'Veterinarios',
        'servicios.index' => 'Catálogo de servicios',
        'vacunas.index' => 'Vacunas',
        'pagos.index' => 'Pagos',
        'proveedores.index' => 'Proveedores',
        'categorias.index' => 'Categorías',
        'productos.index' => 'Productos',
        'almacenes.index' => 'Almacenes',
        'notas-compra.index' => 'Notas de compra',
        'notas-venta.index' => 'Notas de venta',
        'reportes.index' => 'Reportes',
        'reportes.bitacora' => 'Bitácora del sistema',
        'reportes.matriz-acceso' => 'Matriz de acceso',
        'calidad.prompt.index' => 'Configuración de calidad',
    ];

    private const ACCIONES_RUTA = [
        'usuarios.destroy' => ['accion' => 'baja', 'modulo' => 'usuarios', 'descripcion' => 'Usuario dado de baja'],
        'consultas-medicas.cambiar-estado' => ['accion' => 'cambiar_estado', 'modulo' => 'consultas_medicas', 'descripcion' => 'Estado de consulta modificado'],
        'consultas-medicas.iniciar-atencion' => ['accion' => 'iniciar_atencion', 'modulo' => 'consultas_medicas', 'descripcion' => 'Atención médica iniciada'],
        'consultas-medicas.check-in' => ['accion' => 'check_in', 'modulo' => 'consultas_medicas', 'descripcion' => 'Llegada del paciente registrada'],
        'consultas-medicas.registro-llegada' => ['accion' => 'registro_llegada', 'modulo' => 'consultas_medicas', 'descripcion' => 'Registro de llegada completado'],
        'consultas-medicas.reprogramar' => ['accion' => 'reprogramar', 'modulo' => 'consultas_medicas', 'descripcion' => 'Consulta reprogramada'],
        'consultas-medicas.reprogramar-tarde' => ['accion' => 'reprogramar', 'modulo' => 'consultas_medicas', 'descripcion' => 'Consulta reprogramada por tardanza'],
        'pagos.cuotas.pagar' => ['accion' => 'pagar_cuota', 'modulo' => 'pagos', 'descripcion' => 'Cuota de crédito pagada'],
        'calidad.prompt.generate' => ['accion' => 'generar', 'modulo' => 'calidad', 'descripcion' => 'Contenido generado con IA'],
        'portal.reservas.store' => ['accion' => 'crear', 'modulo' => 'portal', 'descripcion' => 'Reserva creada desde portal'],
    ];

    public static function accionLabel(string $accion): string
    {
        return self::ETIQUETAS_ACCION[$accion] ?? ucfirst(str_replace('_', ' ', $accion));
    }

    public static function moduloLabel(string $modulo): string
    {
        return self::ETIQUETAS_MODULO[$modulo] ?? ucfirst(str_replace('_', ' ', $modulo));
    }

    public static function debeExcluirRuta(?string $routeName): bool
    {
        return $routeName !== null && in_array($routeName, self::RUTAS_EXCLUIDAS, true);
    }

    public static function describirNavegacion(?string $routeName, string $path): string
    {
        if ($routeName && isset(self::PAGINAS[$routeName])) {
            return 'Visitó: '.self::PAGINAS[$routeName];
        }

        if ($routeName) {
            return 'Visitó: '.self::formatearNombreRuta($routeName);
        }

        return 'Visitó: /'.ltrim($path, '/');
    }

    public static function inferirDesdeRequest(Request $request): ?array
    {
        $routeName = $request->route()?->getName();

        if ($routeName === null || self::debeExcluirRuta($routeName)) {
            return null;
        }

        if (isset(self::ACCIONES_RUTA[$routeName])) {
            $def = self::ACCIONES_RUTA[$routeName];

            return [
                'accion' => $def['accion'],
                'modulo' => $def['modulo'],
                'descripcion' => self::enriquecerDescripcion($def['descripcion'], $request),
            ];
        }

        $accion = self::accionDesdeMetodo($request->method(), $routeName);
        if ($accion === null) {
            return null;
        }

        $modulo = self::moduloDesdeRuta($routeName);
        $recurso = self::recursoDesdeRuta($routeName);
        $descripcion = self::descripcionDesdeAccion($accion, $recurso, $request);

        return compact('accion', 'modulo', 'descripcion');
    }

    private static function accionDesdeMetodo(string $method, string $routeName): ?string
    {
        if (str_ends_with($routeName, '.destroy') || str_ends_with($routeName, '.eliminar')) {
            return 'eliminar';
        }

        return match ($method) {
            'POST' => 'crear',
            'PUT', 'PATCH' => 'editar',
            'DELETE' => 'eliminar',
            default => null,
        };
    }

    private static function moduloDesdeRuta(string $routeName): string
    {
        $partes = explode('.', $routeName);
        $prefijo = $partes[0] ?? 'sistema';

        return str_replace('-', '_', $prefijo);
    }

    private static function recursoDesdeRuta(string $routeName): string
    {
        $partes = explode('.', $routeName);
        $base = $partes[0] ?? 'recurso';

        return self::moduloLabel(str_replace('-', '_', $base));
    }

    private static function descripcionDesdeAccion(string $accion, string $recurso, Request $request): string
    {
        $id = self::extraerId($request);
        $sufijo = $id ? " #{$id}" : '';

        return match ($accion) {
            'crear' => "{$recurso} creado{$sufijo}",
            'editar' => "{$recurso} actualizado{$sufijo}",
            'eliminar' => "{$recurso} eliminado{$sufijo}",
            'baja' => "{$recurso} dado de baja{$sufijo}",
            default => "{$recurso}: {$accion}{$sufijo}",
        };
    }

    private static function enriquecerDescripcion(string $base, Request $request): string
    {
        $id = self::extraerId($request);

        return $id ? "{$base} #{$id}" : $base;
    }

    private static function extraerId(Request $request): ?string
    {
        foreach (['id', 'customer', 'pet', 'veterinario', 'user', 'cuotaId', 'warehouseId', 'inventoryId', 'medicamentId'] as $param) {
            $valor = $request->route($param);
            if ($valor !== null && (is_numeric($valor) || is_string($valor))) {
                return (string) $valor;
            }
        }

        return null;
    }

    private static function formatearNombreRuta(string $routeName): string
    {
        $partes = explode('.', $routeName);
        $modulo = str_replace('-', ' ', $partes[0] ?? '');
        $accion = match (end($partes)) {
            'index' => 'listado',
            'store' => 'creación',
            'update' => 'actualización',
            'destroy' => 'eliminación',
            'search' => 'búsqueda',
            default => str_replace('-', ' ', end($partes)),
        };

        return ucfirst(trim("{$modulo} — {$accion}"));
    }
}
