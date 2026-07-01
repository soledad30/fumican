<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import {
    FwbTable, FwbTableBody, FwbTableCell, FwbTableHead, FwbTableHeadCell,
    FwbTableRow, FwbButton, FwbPagination, FwbBadge,
} from "flowbite-vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";

const props = defineProps({
    registros: Object,
    estadisticas: Object,
    filters: Object,
    acciones: Array,
    modulos: Array,
});

const filters = ref({
    accion: props.filters?.accion || "",
    modulo: props.filters?.modulo || "",
    search_term: props.filters?.search_term || "",
    fecha_desde: props.filters?.fecha_desde || "",
    fecha_hasta: props.filters?.fecha_hasta || "",
});

const currentPage = ref(props.registros.current_page || 1);

watch(currentPage, (page) => {
    router.get(route("reportes.bitacora"), { ...filters.value, page }, { preserveState: true });
});

function applyFilters() {
    currentPage.value = 1;
    router.get(route("reportes.bitacora"), filters.value, { preserveState: true, replace: true });
}

function resetFilters() {
    filters.value = {
        accion: "", modulo: "", search_term: "", fecha_desde: "", fecha_hasta: "",
    };
    applyFilters();
}

function badgeColor(accion) {
    if (accion?.includes("fallido")) return "red";
    if (accion === "login_exitoso" || accion === "crear") return "green";
    if (accion === "eliminar" || accion === "baja") return "yellow";
    if (accion === "acceso" || accion === "navegar") return "indigo";
    if (accion === "editar" || accion === "cambiar_estado") return "blue";
    return "dark";
}
</script>

<template>
    <AdminLayout title="Bitácora">
        <div class="py-6">
            <div class="vet-section-header mb-4">
                <h1 class="text-2xl font-bold vet-page-title">Bitácora del sistema</h1>
                <Link :href="route('reportes.index')">
                    <FwbButton color="alternative" size="sm">← Volver a reportes</FwbButton>
                </Link>
            </div>

            <p class="mb-4 vet-page-subtitle">
                Logins exitosos: <strong>{{ estadisticas?.logins_exitosos ?? 0 }}</strong> |
                Fallidos: <strong>{{ estadisticas?.logins_fallidos ?? 0 }}</strong>
            </p>

            <form class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6 vet-filter-panel" @submit.prevent="applyFilters">
                <div>
                    <InputLabel value="Acción" />
                    <select v-model="filters.accion" class="w-full rounded border-gray-300 dark:bg-gray-700 text-sm">
                        <option value="">Todas</option>
                        <option v-for="a in acciones" :key="a" :value="a">{{ a }}</option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Módulo" />
                    <select v-model="filters.modulo" class="w-full rounded border-gray-300 dark:bg-gray-700 text-sm">
                        <option value="">Todos</option>
                        <option v-for="m in modulos" :key="m" :value="m">{{ m }}</option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Desde" />
                    <TextInput v-model="filters.fecha_desde" type="date" class="w-full" />
                </div>
                <div>
                    <InputLabel value="Hasta" />
                    <TextInput v-model="filters.fecha_hasta" type="date" class="w-full" />
                </div>
                <div class="md:col-span-2">
                    <InputLabel value="Buscar" />
                    <TextInput v-model="filters.search_term" placeholder="Usuario, descripción, IP..." class="w-full" />
                </div>
                <div class="md:col-span-6 flex gap-2">
                    <FwbButton color="green" type="submit">Filtrar</FwbButton>
                    <FwbButton color="alternative" type="button" @click="resetFilters">Limpiar</FwbButton>
                </div>
            </form>

            <FwbTable>
                <FwbTableHead>
                    <FwbTableHeadCell>Fecha</FwbTableHeadCell>
                    <FwbTableHeadCell>Usuario</FwbTableHeadCell>
                    <FwbTableHeadCell>Acción</FwbTableHeadCell>
                    <FwbTableHeadCell>Módulo</FwbTableHeadCell>
                    <FwbTableHeadCell>Descripción</FwbTableHeadCell>
                    <FwbTableHeadCell>IP</FwbTableHeadCell>
                </FwbTableHead>
                <FwbTableBody>
                    <FwbTableRow v-for="r in registros.data" :key="r.id">
                        <FwbTableCell class="text-sm whitespace-nowrap">{{ r.creado_en }}</FwbTableCell>
                        <FwbTableCell class="text-sm">
                            <span class="font-medium">{{ r.usuario }}</span>
                            <span v-if="r.usuario_email" class="block text-xs text-gray-500 truncate max-w-[10rem]" :title="r.usuario_email">
                                {{ r.usuario_email }}
                            </span>
                        </FwbTableCell>
                        <FwbTableCell>
                            <FwbBadge :color="badgeColor(r.accion)">{{ r.accion_label || r.accion }}</FwbBadge>
                        </FwbTableCell>
                        <FwbTableCell>{{ r.modulo_label || r.modulo }}</FwbTableCell>
                        <FwbTableCell class="max-w-md" :title="r.descripcion">{{ r.descripcion }}</FwbTableCell>
                        <FwbTableCell>{{ r.ip }}</FwbTableCell>
                    </FwbTableRow>
                    <FwbTableRow v-if="!registros.data?.length">
                        <FwbTableCell colspan="6" class="text-center text-gray-500 py-6">
                            No hay registros con los filtros aplicados.
                        </FwbTableCell>
                    </FwbTableRow>
                </FwbTableBody>
            </FwbTable>

            <div class="mt-4 flex justify-center">
                <FwbPagination v-model="currentPage" :total-pages="registros.last_page" />
            </div>
        </div>
    </AdminLayout>
</template>
