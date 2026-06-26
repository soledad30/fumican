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
        return nombresBd.value.includes(bd);
    }

    return { puede, esAdmin, nombresBd };
}
