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
import InputError from "@/Components/InputError.vue";
import TextInput from "@/Components/TextInput.vue";
import TableActionButtons from "@/Components/TableActionButtons.vue";
import { usePermisos } from "@/Composables/usePermisos";
import { useFormErrors } from "@/Composables/useFormErrors";

const props = defineProps({ especies: Object });
const { formErrors, clearErrors, fromAxios, get } = useFormErrors();
const currentPage = ref(props.especies.current_page || 1);
const showToast = ref(false);
const toastMsg = ref("");
const toastType = ref("success");
const isModal = ref(false);
const isDeleteModal = ref(false);
const loading = ref(false);
const selected = ref(null);
const form = ref({ nombre: "" });

const { puede } = usePermisos();
const canCreate = computed(() => puede("crear especies"));
const canEdit = computed(() => puede("editar especies"));

watch(currentPage, (page) => {
    router.get(route("especies.index"), { page }, { preserveState: true });
});

function toast(type, msg) {
    toastType.value = type;
    toastMsg.value = msg;
    showToast.value = true;
    setTimeout(() => (showToast.value = false), 3000);
}

function openCreate() {
    selected.value = null;
    form.value = { nombre: "" };
    clearErrors();
    isModal.value = true;
}

function openEdit(e) {
    selected.value = e;
    form.value = { nombre: e.nombre ?? e.name };
    clearErrors();
    isModal.value = true;
}

function openDelete(e) {
    selected.value = e;
    isDeleteModal.value = true;
}

async function submit() {
    loading.value = true;
    clearErrors();
    try {
        const url = selected.value
            ? route("especies.update", selected.value.id)
            : route("especies.store");
        const method = selected.value ? axios.put : axios.post;
        const { data } = await method(url, form.value);
        toast("success", data.message);
        isModal.value = false;
        router.reload();
    } catch (e) {
        toast("danger", fromAxios(e) || "Error al guardar");
    } finally {
        loading.value = false;
    }
}

async function confirmDelete() {
    loading.value = true;
    try {
        const { data } = await axios.delete(route("especies.destroy", selected.value.id));
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
    <AdminLayout title="Especies">
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType">{{ toastMsg }}</FwbToast>
        </div>

        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Especies</h2>
            <FwbButton v-if="canCreate" color="green" @click="openCreate">Nueva especie</FwbButton>
        </div>

        <FwbTable>
            <FwbTableHead>
                <FwbTableHeadCell>Nombre</FwbTableHeadCell>
                <FwbTableHeadCell>Acciones</FwbTableHeadCell>
            </FwbTableHead>
            <FwbTableBody>
                <FwbTableRow v-for="e in especies.data" :key="e.id">
                    <FwbTableCell>{{ e.nombre ?? e.name }}</FwbTableCell>
                    <FwbTableCell>
                        <TableActionButtons
                            :can-edit="canEdit"
                            :can-delete="canEdit"
                            @edit="openEdit(e)"
                            @delete="openDelete(e)"
                        />
                    </FwbTableCell>
                </FwbTableRow>
            </FwbTableBody>
        </FwbTable>

        <div class="mt-4 flex justify-center">
            <FwbPagination v-model="currentPage" :total-pages="especies.last_page" />
        </div>

        <FwbModal v-if="isModal" @close="isModal = false">
            <template #header><h3>{{ selected ? "Editar" : "Nueva" }} especie</h3></template>
            <template #body>
                <div>
                    <InputLabel value="Nombre" />
                    <TextInput v-model="form.nombre" class="w-full" />
                    <InputError :message="get('nombre')" />
                </div>
            </template>
            <template #footer>
                <FwbButton color="alternative" @click="isModal = false">Cancelar</FwbButton>
                <FwbButton color="green" :disabled="loading" @click="submit">Guardar</FwbButton>
            </template>
        </FwbModal>

        <FwbModal v-if="isDeleteModal" @close="isDeleteModal = false">
            <template #header><h3>Confirmar eliminación</h3></template>
            <template #body>¿Eliminar la especie «{{ selected?.nombre ?? selected?.name }}»?</template>
            <template #footer>
                <FwbButton color="alternative" @click="isDeleteModal = false">Cancelar</FwbButton>
                <FwbButton color="red" :disabled="loading" @click="confirmDelete">Eliminar</FwbButton>
            </template>
        </FwbModal>
    </AdminLayout>
</template>
