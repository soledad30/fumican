<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link } from "@inertiajs/vue3";
import { FwbButton } from "flowbite-vue";
import { computed } from "vue";

const props = defineProps({
    matriz: Array,
    permisos: Array,
    roles: Array,
});

const ACCENTOS = {
    medicas: "médicas",
    medica: "médica",
    bitacora: "bitácora",
    categorias: "categorías",
    categoria: "categoría",
    paginas: "páginas",
    pagina: "página",
    descripcion: "descripción",
    direccion: "dirección",
    telefono: "teléfono",
    almacen: "almacén",
    almacenes: "almacenes",
    especies: "especies",
    vacunas: "vacunas",
};

function formatPermiso(nombre) {
    if (!nombre) return "";
    let label = nombre.replace(/_/g, " ").trim().toLowerCase();
    for (const [from, to] of Object.entries(ACCENTOS)) {
        label = label.replace(new RegExp(`\\b${from}\\b`, "g"), to);
    }
    return label.charAt(0).toUpperCase() + label.slice(1);
}

function formatRol(nombre) {
    const map = {
        administrador: "Administrador",
        veterinario: "Veterinario",
        cliente: "Cliente",
        propietario: "Propietario",
    };
    return map[nombre?.toLowerCase()] ?? nombre;
}

function contarPermisos(fila) {
    return fila.permisos?.filter((p) => p.asignado).length ?? 0;
}

const totalPermisos = computed(() => props.permisos?.length ?? 0);
</script>

<template>
    <AdminLayout title="Matriz de acceso">
        <div class="vet-section-header">
            <div>
                <h1 class="text-2xl font-bold vet-page-title">Matriz de control de acceso</h1>
                <p class="vet-page-subtitle mt-1">
                    Permisos asignados por rol — veterinario, cliente, propietario
                    <span class="opacity-75">(administrador es permiso de sistema)</span>
                </p>
            </div>
            <Link :href="route('reportes.index')">
                <FwbButton color="alternative" size="sm">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Volver a reportes
                </FwbButton>
            </Link>
        </div>

        <div class="vet-matrix-legend">
            <span>
                <span class="dot-yes"><i class="fa-solid fa-check"></i></span>
                Permiso asignado
            </span>
            <span>
                <span class="dot-no">—</span>
                Sin permiso
            </span>
            <span>
                <i class="fa-solid fa-arrows-left-right text-emerald-600"></i>
                Desplázate horizontalmente para ver todos los permisos
            </span>
        </div>

        <div class="vet-matrix-wrap">
            <table class="vet-matrix-table">
                <thead>
                    <tr>
                        <th class="vet-matrix-corner" scope="col">
                            <i class="fa-solid fa-user-shield mr-1"></i> Rol / Permiso
                        </th>
                        <th
                            v-for="p in permisos"
                            :key="p.id"
                            class="vet-matrix-col"
                            scope="col"
                            :title="formatPermiso(p.nombre)"
                        >
                            {{ formatPermiso(p.nombre) }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="fila in matriz" :key="fila.id">
                        <th class="vet-matrix-role" scope="row">
                            {{ formatRol(fila.nombre) }}
                            <span class="vet-matrix-role__count">
                                {{ contarPermisos(fila) }} / {{ totalPermisos }} permisos
                            </span>
                        </th>
                        <td
                            v-for="p in fila.permisos"
                            :key="p.id"
                            class="vet-matrix-cell"
                            :class="{ 'vet-matrix-cell--yes': p.asignado }"
                            :title="`${formatRol(fila.nombre)} — ${formatPermiso(p.nombre)}: ${p.asignado ? 'Sí' : 'No'}`"
                        >
                            <i v-if="p.asignado" class="fa-solid fa-check text-sm"></i>
                            <span v-else class="text-slate-300">—</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
