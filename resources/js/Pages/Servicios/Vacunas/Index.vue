<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { router } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";
import axios from "axios";
import { usePermisos } from "@/Composables/usePermisos";
import {
    FwbButton, FwbTable, FwbTableHead, FwbTableHeadCell,
    FwbTableBody, FwbTableRow, FwbTableCell, FwbPagination,
    FwbModal, FwbToast, FwbTabs, FwbTab,
} from "flowbite-vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import TableActionButtons from "@/Components/TableActionButtons.vue";

const props = defineProps({
    vacunas: Object,
    historial: Object,
    filters: Object,
    vacunasOpciones: { type: Array, default: () => [] },
    mascotasOpciones: { type: Array, default: () => [] },
});
const activeTab = ref("catalogo");
const filters = ref({ search_term: props.filters.search_term || "" });
const currentPage = ref(props.vacunas.current_page || 1);
const showToast = ref(false);
const { puede } = usePermisos();
const canCreate = computed(() => puede("crear vacunas"));
const canEdit = computed(() => puede("editar vacunas"));
const toastMsg = ref("");
const toastType = ref("success");
const isVacunaModal = ref(false);
const isHistorialModal = ref(false);
const isDeleteModal = ref(false);
const deleteType = ref("vacuna");
const loading = ref(false);
const selected = ref(null);
const formVacuna = ref({ nombre: "", duracion_dias: 365, notas: "" });
const formHistorial = ref({ mascota_id: "", vacuna_id: "", fecha_aplicacion: "", fecha_proxima: "", notas: "" });

watch(currentPage, (page) => {
    router.get(route("vacunas.search"), { ...filters.value, page }, { preserveState: true });
});

function toast(type, msg) {
    toastType.value = type; toastMsg.value = msg; showToast.value = true;
    setTimeout(() => (showToast.value = false), 3000);
}

function openCreateVacuna() {
    selected.value = null;
    formVacuna.value = { nombre: "", duracion_dias: 365, notas: "" };
    isVacunaModal.value = true;
}

function openEditVacuna(v) {
    selected.value = v;
    formVacuna.value = { nombre: v.nombre, duracion_dias: v.duracion_dias, notas: v.notas || "" };
    isVacunaModal.value = true;
}

function openCreateHistorial() {
    selected.value = null;
    formHistorial.value = { mascota_id: "", vacuna_id: "", fecha_aplicacion: "", fecha_proxima: "", notas: "" };
    isHistorialModal.value = true;
}

async function submitVacuna() {
    loading.value = true;
    try {
        const url = selected.value ? route("vacunas.update", selected.value.id) : route("vacunas.store");
        const method = selected.value ? axios.put : axios.post;
        const { data } = await method(url, formVacuna.value);
        toast("success", data.message);
        isVacunaModal.value = false;
        router.reload();
    } catch (e) {
        toast("danger", e.response?.data?.message || "Error al guardar vacuna");
    } finally { loading.value = false; }
}

async function submitHistorial() {
    loading.value = true;
    try {
        const { data } = await axios.post(route("vacunas.historial.store"), formHistorial.value);
        toast("success", data.message);
        isHistorialModal.value = false;
        router.reload();
    } catch (e) {
        toast("danger", e.response?.data?.message || "Error al registrar historial");
    } finally { loading.value = false; }
}

function openDelete(type, item) {
    deleteType.value = type;
    selected.value = item;
    isDeleteModal.value = true;
}

async function confirmDelete() {
    loading.value = true;
    try {
        const url = deleteType.value === "vacuna"
            ? route("vacunas.destroy", selected.value.id)
            : route("vacunas.historial.destroy", selected.value.id);
        const { data } = await axios.delete(url);
        toast("success", data.message);
        isDeleteModal.value = false;
        router.reload();
    } catch (e) {
        toast("danger", e.response?.data?.message || "Error al eliminar");
    } finally { loading.value = false; }
}
</script>

<template>
    <AdminLayout title="Vacunas e Historial">
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType">{{ toastMsg }}</FwbToast>
        </div>

        <FwbTabs v-model="activeTab">
            <FwbTab name="catalogo" title="Catálogo de vacunas">
                <div class="flex justify-between my-4">
                    <TextInput v-model="filters.search_term" placeholder="Buscar..." class="w-64" @keyup.enter="router.get(route('vacunas.search'), filters)" />
                    <FwbButton v-if="canCreate" color="green" @click="openCreateVacuna">Nueva vacuna</FwbButton>
                </div>
                <FwbTable>
                    <FwbTableHead>
                        <FwbTableHeadCell>Nombre</FwbTableHeadCell>
                        <FwbTableHeadCell>Duración (días)</FwbTableHeadCell>
                        <FwbTableHeadCell>Notas</FwbTableHeadCell>
                        <FwbTableHeadCell>Acciones</FwbTableHeadCell>
                    </FwbTableHead>
                    <FwbTableBody>
                        <FwbTableRow v-for="v in vacunas.data" :key="v.id">
                            <FwbTableCell>{{ v.nombre }}</FwbTableCell>
                            <FwbTableCell>{{ v.duracion_dias }}</FwbTableCell>
                            <FwbTableCell>{{ v.notas }}</FwbTableCell>
                            <FwbTableCell>
                                <TableActionButtons
                                    :can-edit="canEdit"
                                    :can-delete="canEdit"
                                    @edit="openEditVacuna(v)"
                                    @delete="openDelete('vacuna', v)"
                                />
                            </FwbTableCell>
                        </FwbTableRow>
                    </FwbTableBody>
                </FwbTable>
                <div class="mt-4 flex justify-center">
                    <FwbPagination v-model="currentPage" :total-pages="vacunas.last_page" />
                </div>
            </FwbTab>

            <FwbTab name="historial" title="Historial de vacunación">
                <div class="flex justify-end my-4">
                    <FwbButton v-if="canCreate" color="green" @click="openCreateHistorial">Registrar aplicación</FwbButton>
                </div>
                <FwbTable>
                    <FwbTableHead>
                        <FwbTableHeadCell>Mascota</FwbTableHeadCell>
                        <FwbTableHeadCell>Vacuna</FwbTableHeadCell>
                        <FwbTableHeadCell>Fecha aplicación</FwbTableHeadCell>
                        <FwbTableHeadCell>Próxima dosis</FwbTableHeadCell>
                        <FwbTableHeadCell>Veterinario</FwbTableHeadCell>
                        <FwbTableHeadCell>Acciones</FwbTableHeadCell>
                    </FwbTableHead>
                    <FwbTableBody>
                        <FwbTableRow v-for="h in historial.data" :key="h.id">
                            <FwbTableCell>{{ h.mascota?.nombre }}</FwbTableCell>
                            <FwbTableCell>{{ h.vacuna?.nombre }}</FwbTableCell>
                            <FwbTableCell>{{ h.fecha_aplicacion }}</FwbTableCell>
                            <FwbTableCell>{{ h.fecha_proxima || "—" }}</FwbTableCell>
                            <FwbTableCell>{{ h.veterinario?.nombre || h.veterinario?.full_name }}</FwbTableCell>
                            <FwbTableCell>
                                <TableActionButtons
                                    :can-edit="false"
                                    :can-delete="canEdit"
                                    @delete="openDelete('historial', h)"
                                />
                            </FwbTableCell>
                        </FwbTableRow>
                    </FwbTableBody>
                </FwbTable>
            </FwbTab>
        </FwbTabs>

        <FwbModal v-if="isVacunaModal" @close="isVacunaModal = false">
            <template #header><h3>{{ selected ? "Editar" : "Nueva" }} vacuna</h3></template>
            <template #body>
                <div class="space-y-4">
                    <div><InputLabel value="Nombre" /><TextInput v-model="formVacuna.nombre" class="w-full" /></div>
                    <div><InputLabel value="Duración (días)" /><TextInput v-model="formVacuna.duracion_dias" type="number" class="w-full" /></div>
                    <div><InputLabel value="Notas" /><TextInput v-model="formVacuna.notas" class="w-full" /></div>
                </div>
            </template>
            <template #footer>
                <FwbButton color="alternative" @click="isVacunaModal = false">Cancelar</FwbButton>
                <FwbButton color="green" :disabled="loading" @click="submitVacuna">Guardar</FwbButton>
            </template>
        </FwbModal>

        <FwbModal v-if="isHistorialModal" @close="isHistorialModal = false">
            <template #header><h3>Registrar vacunación</h3></template>
            <template #body>
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Mascota" />
                        <select
                            v-model="formHistorial.mascota_id"
                            class="w-full border rounded px-2 py-2 dark:bg-gray-700 dark:border-gray-600"
                        >
                            <option value="" disabled>— Seleccione una mascota —</option>
                            <option v-for="m in mascotasOpciones" :key="m.id" :value="m.id">
                                {{ m.nombre }} — {{ m.owner?.nombre }} {{ m.owner?.apellido }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Vacuna" />
                        <select
                            v-model="formHistorial.vacuna_id"
                            class="w-full border rounded px-2 py-2 dark:bg-gray-700 dark:border-gray-600"
                        >
                            <option value="" disabled>— Seleccione una vacuna —</option>
                            <option v-for="v in vacunasOpciones" :key="v.id" :value="v.id">
                                {{ v.nombre }}
                            </option>
                        </select>
                    </div>
                    <div><InputLabel value="Fecha aplicación" /><TextInput v-model="formHistorial.fecha_aplicacion" type="date" class="w-full" /></div>
                    <div><InputLabel value="Próxima dosis" /><TextInput v-model="formHistorial.fecha_proxima" type="date" class="w-full" /></div>
                    <div><InputLabel value="Notas" /><TextInput v-model="formHistorial.notas" class="w-full" /></div>
                </div>
            </template>
            <template #footer>
                <FwbButton color="alternative" @click="isHistorialModal = false">Cancelar</FwbButton>
                <FwbButton color="green" :disabled="loading" @click="submitHistorial">Guardar</FwbButton>
            </template>
        </FwbModal>

        <FwbModal v-if="isDeleteModal" @close="isDeleteModal = false">
            <template #header><h3>Confirmar eliminación</h3></template>
            <template #body>¿Está seguro de eliminar este registro?</template>
            <template #footer>
                <FwbButton color="alternative" @click="isDeleteModal = false">Cancelar</FwbButton>
                <FwbButton color="red" :disabled="loading" @click="confirmDelete">Eliminar</FwbButton>
            </template>
        </FwbModal>
    </AdminLayout>
</template>
