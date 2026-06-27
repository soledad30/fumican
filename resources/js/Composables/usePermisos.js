import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Comprueba permisos del usuario contra nombres de BD (snake_case)
 * o claves del enum de aplicación ("crear clientes", etc.).
 */
export function usePermisos() {
    const page = usePage();

    const nombresBd = computed(() => {
        const perms = page.props.auth?.user_permissions ?? [];
        return perms.map((p) => p.nombre ?? p.name).filter(Boolean);
    });

    const mapa = computed(() => page.props.auth?.permisos_mapa ?? {});

    const paquetes = {
        gestionar_usuarios: ['crear_usuarios', 'editar_usuarios'],
        gestionar_mascotas: ['crear_mascotas', 'editar_mascotas', 'eliminar_mascotas'],
        gestionar_veterinarios: ['crear_veterinarios', 'editar_veterinarios', 'eliminar_veterinarios'],
        gestionar_consultas: ['crear_consultas', 'editar_consultas', 'eliminar_consultas'],
        gestionar_historial: ['crear_historial', 'editar_historial', 'eliminar_historial'],
        gestionar_servicios: ['crear_servicios', 'editar_servicios', 'eliminar_servicios'],
        gestionar_pagos: ['crear_pagos', 'editar_pagos', 'eliminar_pagos'],
        gestionar_productos: ['crear_productos', 'editar_productos', 'eliminar_productos', 'crear_categorias', 'editar_categorias', 'eliminar_categorias'],
        gestionar_inventarios: ['crear_inventarios', 'editar_inventarios', 'eliminar_inventarios'],
        gestionar_ventas: ['crear_ventas', 'editar_ventas', 'eliminar_ventas'],
        gestionar_compras: ['crear_compras', 'editar_compras', 'eliminar_compras', 'crear_proveedores', 'editar_proveedores', 'eliminar_proveedores'],
    };

    const esAdmin = computed(() => {
        const rol =
            page.props.auth?.user?.role?.name
            ?? page.props.auth?.user?.role?.nombre
            ?? '';
        if (['propietario', 'administrador'].includes(rol)) {
            return true;
        }
        return nombresBd.value.includes('administrar_sistema');
    });

    function resolverNombreBd(permiso) {
        if (mapa.value[permiso]) {
            return mapa.value[permiso];
        }
        if (permiso.includes(' ')) {
            return permiso.replace(/ /g, '_');
        }
        return permiso;
    }

    function puede(permiso) {
        if (esAdmin.value) {
            return true;
        }
        const bd = resolverNombreBd(permiso);
        if (nombresBd.value.includes(bd)) {
            return true;
        }
        for (const [gestionar, incluidos] of Object.entries(paquetes)) {
            if (incluidos.includes(bd) && nombresBd.value.includes(gestionar)) {
                return true;
            }
        }
        return false;
    }

    return { puede, esAdmin, nombresBd };
}
