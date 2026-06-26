<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { router } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";
import axios from "axios";
import {
    FwbButton, FwbTable, FwbTableHead, FwbTableHeadCell,
    FwbTableBody, FwbTableRow, FwbTableCell, FwbPagination,
    FwbModal, FwbToast,
} from "flowbite-vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import TableActionButtons from "@/Components/TableActionButtons.vue";
import { usePermisos } from "@/Composables/usePermisos";

const props = defineProps({ razas: Object, especies: Array });
const currentPage = ref(props.razas.current_page || 1);
const showToast = ref(false);
const toastMsg = ref("");
const toastType = ref("success");
const isModal = ref(false);
const isDeleteModal = ref(false);
const loading = ref(false);
const selected = ref(null);
const form = ref({ nombre: "", especie_id: "" });

const { puede } = usePermisos();
const canCreate = computed(() => puede("crear mascotas"));
const canEdit = computed(() => puede("editar mascotas"));

watch(currentPage, (page) => {
    router.get(route("razas.index"), { page }, { preserveState: true });
});

function toast(type, msg) {
    toastType.value = type;
    toastMsg.value = msg;
    showToast.value = true;
    setTimeout(() => (showToast.value = false), 3000);
}

function openCreate() {
    selected.value = null;
    form.value = { nombre: "", especie_id: props.especies[0]?.id ?? "" };
    isModal.value = true;
}

function openEdit(r) {
    selected.value = r;
    form.value = {
        nombre: r.nombre ?? r.name,
        especie_id: r.especie_id ?? r.specie_id ?? r.especie?.id,
    };
    isModal.value = true;
}

function openDelete(r) {
    selected.value = r;
    isDeleteModal.value = true;
}

async function submit() {
    loading.value = true;
    try {
        const url = selected.value
            ? route("razas.update", selected.value.id)
            : route("razas.store");
        const method = selected.value ? axios.put : axios.post;
        const { data } = await method(url, form.value);
        toast("success", data.message);
        isModal.value = false;
        router.reload();
    } catch (e) {
        toast("danger", e.response?.data?.message || "Error al guardar");
    } finally {
        loading.value = false;
    }
}

async function confirmDelete() {
    loading.value = true;
    try {
        const { data } = await axios.delete(route("razas.destroy", selected.value.id));
        toast("success", data.message);
        isDeleteModal.value = false;
        router.reload();
    } catch (e) {
        toast("danger", e.response?.data?.message || "Error al eliminar");
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <AdminLayout title="Razas">
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType">{{ toastMsg }}</FwbToast>
        </div>

        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Razas</h2>
            <FwbButton v-if="canCreate" color="green" @click="openCreate">Nueva raza</FwbButton>
        </div>

        <FwbTable>
            <FwbTableHead>
                <FwbTableHeadCell>Nombre</FwbTableHeadCell>
                <FwbTableHeadCell>Especie</FwbTableHeadCell>
                <FwbTableHeadCell>Acciones</FwbTableHeadCell>
            </FwbTableHead>
            <FwbTableBody>
                <FwbTableRow v-for="r in razas.data" :key="r.id">
                    <FwbTableCell>{{ r.nombre ?? r.name }}</FwbTableCell>
                    <FwbTableCell>{{ r.especie?.nombre ?? r.especie?.name ?? "—" }}</FwbTableCell>
                    <FwbTableCell>
                        <TableActionButtons
                            :can-edit="canEdit"
                            :can-delete="canEdit"
                            @edit="openEdit(r)"
                            @delete="openDelete(r)"
                        />
                    </FwbTableCell>
                </FwbTableRow>
            </FwbTableBody>
        </FwbTable>

        <div class="mt-4 flex justify-center">
            <FwbPagination v-model="currentPage" :total-pages="razas.last_page" />
        </div>

        <FwbModal v-if="isModal" @close="isModal = false">
            <template #header><h3>{{ selected ? "Editar" : "Nueva" }} raza</h3></template>
            <template #body>
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Especie" />
                        <select v-model="form.especie_id" class="w-full rounded border-gray-300 dark:bg-gray-700">
                            <option v-for="e in especies" :key="e.id" :value="e.id">
                                {{ e.nombre ?? e.name }}
                            </option>
                        </select>
                    </div>
                    <div><InputLabel value="Nombre" /><TextInput v-model="form.nombre" class="w-full" /></div>
                </div>
            </template>
            <template #footer>
                <FwbButton color="alternative" @click="isModal = false">Cancelar</FwbButton>
                <FwbButton color="green" :disabled="loading" @click="submit">Guardar</FwbButton>
            </template>
        </FwbModal>

        <FwbModal v-if="isDeleteModal" @close="isDeleteModal = false">
            <template #header><h3>Confirmar eliminación</h3></template>
            <template #body>¿Eliminar la raza «{{ selected?.nombre ?? selected?.name }}»?</template>
            <template #footer>
                <FwbButton color="alternative" @click="isDeleteModal = false">Cancelar</FwbButton>
                <FwbButton color="red" :disabled="loading" @click="confirmDelete">Eliminar</FwbButton>
            </template>
        </FwbModal>
    </AdminLayout>
</template>
