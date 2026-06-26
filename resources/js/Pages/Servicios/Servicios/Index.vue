<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { router } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";
import axios from "axios";
import { usePermisos } from "@/Composables/usePermisos";
import {
    FwbButton, FwbTable, FwbTableHead, FwbTableHeadCell,
    FwbTableBody, FwbTableRow, FwbTableCell, FwbPagination,
    FwbModal, FwbToast, FwbCheckbox,
} from "flowbite-vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import TableActionButtons from "@/Components/TableActionButtons.vue";

const props = defineProps({ servicios: Object, filters: Object });
const filters = ref({ search_term: props.filters.search_term || "" });
const currentPage = ref(props.servicios.current_page || 1);
const showToast = ref(false);
const { puede } = usePermisos();
const canCreate = computed(() => puede("crear servicios"));
const canEdit = computed(() => puede("editar servicios"));
const toastMsg = ref("");
const toastType = ref("success");
const isModal = ref(false);
const isDeleteModal = ref(false);
const loading = ref(false);
const selected = ref(null);
const form = ref({ nombre: "", descripcion: "", precio: 0, esta_activo: true });

watch(currentPage, (page) => {
    router.get(route("servicios.search"), { ...filters.value, page }, { preserveState: true });
});

function toast(type, msg) {
    toastType.value = type; toastMsg.value = msg; showToast.value = true;
    setTimeout(() => (showToast.value = false), 3000);
}

function applyFilters() {
    router.get(route("servicios.search"), filters.value, { preserveState: true, replace: true });
}

function openCreate() {
    selected.value = null;
    form.value = { nombre: "", descripcion: "", precio: 0, esta_activo: true };
    isModal.value = true;
}

function openEdit(s) {
    selected.value = s;
    form.value = { nombre: s.nombre, descripcion: s.descripcion || "", precio: s.precio, esta_activo: s.esta_activo ?? true };
    isModal.value = true;
}

function openDelete(s) { selected.value = s; isDeleteModal.value = true; }

async function submit() {
    loading.value = true;
    try {
        const url = selected.value ? route("servicios.update", selected.value.id) : route("servicios.store");
        const method = selected.value ? axios.put : axios.post;
        const { data } = await method(url, form.value);
        toast("success", data.message);
        isModal.value = false;
        router.reload();
    } catch (e) {
        toast("danger", e.response?.data?.message || "Error al guardar");
    } finally { loading.value = false; }
}

async function confirmDelete() {
    loading.value = true;
    try {
        const { data } = await axios.delete(route("servicios.destroy", selected.value.id));
        toast("success", data.message);
        isDeleteModal.value = false;
        router.reload();
    } catch (e) {
        toast("danger", e.response?.data?.message || "Error al eliminar");
    } finally { loading.value = false; }
}
</script>

<template>
    <AdminLayout title="Catálogo de Servicios">
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType">{{ toastMsg }}</FwbToast>
        </div>

        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Servicios</h2>
            <FwbButton v-if="canCreate" color="green" @click="openCreate">Nuevo servicio</FwbButton>
        </div>

        <div class="flex gap-2 mb-4">
            <TextInput v-model="filters.search_term" placeholder="Buscar servicio..." class="w-64" @keyup.enter="applyFilters" />
            <FwbButton @click="applyFilters">Buscar</FwbButton>
        </div>

        <FwbTable>
            <FwbTableHead>
                <FwbTableHeadCell>Nombre</FwbTableHeadCell>
                <FwbTableHeadCell>Descripción</FwbTableHeadCell>
                <FwbTableHeadCell>Precio (Bs.)</FwbTableHeadCell>
                <FwbTableHeadCell>Activo</FwbTableHeadCell>
                <FwbTableHeadCell>Acciones</FwbTableHeadCell>
            </FwbTableHead>
            <FwbTableBody>
                <FwbTableRow v-for="s in servicios.data" :key="s.id">
                    <FwbTableCell>{{ s.nombre }}</FwbTableCell>
                    <FwbTableCell>{{ s.descripcion }}</FwbTableCell>
                    <FwbTableCell>{{ Number(s.precio).toFixed(2) }}</FwbTableCell>
                    <FwbTableCell>{{ s.esta_activo ? "Sí" : "No" }}</FwbTableCell>
                    <FwbTableCell>
                        <TableActionButtons
                            :can-edit="canEdit"
                            :can-delete="canEdit"
                            @edit="openEdit(s)"
                            @delete="openDelete(s)"
                        />
                    </FwbTableCell>
                </FwbTableRow>
            </FwbTableBody>
        </FwbTable>

        <div class="mt-4 flex justify-center">
            <FwbPagination v-model="currentPage" :total-pages="servicios.last_page" />
        </div>

        <FwbModal v-if="isModal" @close="isModal = false">
            <template #header><h3>{{ selected ? "Editar" : "Nuevo" }} servicio</h3></template>
            <template #body>
                <div class="space-y-4">
                    <div><InputLabel value="Nombre" /><TextInput v-model="form.nombre" class="w-full" /></div>
                    <div><InputLabel value="Descripción" /><TextInput v-model="form.descripcion" class="w-full" /></div>
                    <div><InputLabel value="Precio (Bs.)" /><TextInput v-model="form.precio" type="number" step="0.01" class="w-full" /></div>
                    <FwbCheckbox v-model="form.esta_activo" label="Servicio activo" />
                </div>
            </template>
            <template #footer>
                <FwbButton color="alternative" @click="isModal = false">Cancelar</FwbButton>
                <FwbButton color="green" :disabled="loading" @click="submit">Guardar</FwbButton>
            </template>
        </FwbModal>

        <FwbModal v-if="isDeleteModal" @close="isDeleteModal = false">
            <template #header><h3>Confirmar eliminación</h3></template>
            <template #body>¿Eliminar el servicio "{{ selected?.nombre }}"?</template>
            <template #footer>
                <FwbButton color="alternative" @click="isDeleteModal = false">Cancelar</FwbButton>
                <FwbButton color="red" :disabled="loading" @click="confirmDelete">Eliminar</FwbButton>
            </template>
        </FwbModal>
    </AdminLayout>
</template>
