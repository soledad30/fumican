<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref, watch, computed } from "vue";
import axios from "axios";
import { router } from "@inertiajs/vue3";
import { usePermisos } from "@/Composables/usePermisos";
import {
    FwbTable,
    FwbTableHead,
    FwbTableHeadCell,
    FwbTableBody,
    FwbTableRow,
    FwbTableCell,
    FwbButton,
    FwbA,
    FwbPagination,
    FwbModal,
    FwbToast,
} from "flowbite-vue";
import TableActionButtons from "@/Components/TableActionButtons.vue";

// Props
const props = defineProps({ warehouses: Object });
const currentPage = ref(props.warehouses.current_page || 1);

// Paginate
watch(currentPage, (page) => {
    router.get(route("almacenes.index"), { page }, { preserveState: true });
});

// Toast
const showToast = ref(false);
const { puede } = usePermisos();
const canCreate = computed(() => puede("crear almacenes"));
const canEdit = computed(() => puede("editar almacenes"));
const toastMsg = ref("");
const toastType = ref("success");

// Modals & loading
const isCreateModal = ref(false);
const isEditModal = ref(false);
const isDeleteModal = ref(false);
const loading = ref(false);
const deleting = ref(false);

// Form & selected
const form = ref({ name: "", location: "", description: "" });
const selectedWarehouse = ref(null);

// Open modals
function openCreateModal() {
    selectedWarehouse.value = null;
    form.value = { name: "", location: "", description: "" };
    isCreateModal.value = true;
}
function openEditModal(w) {
    selectedWarehouse.value = w;
    form.value = {
        name: w.name,
        location: w.location,
        description: w.description,
    };
    isEditModal.value = true;
}
function openDeleteModal(w) {
    selectedWarehouse.value = w;
    isDeleteModal.value = true;
}

// Close modals
function closeCreateModal() {
    isCreateModal.value = false;
    loading.value = false;
}
function closeEditModal() {
    isEditModal.value = false;
    loading.value = false;
    selectedWarehouse.value = null;
}
function closeDeleteModal() {
    isDeleteModal.value = false;
    deleting.value = false;
    selectedWarehouse.value = null;
}

// Create via Axios
async function submitCreate() {
    if (loading.value) return;
    loading.value = true;
    try {
        const { data } = await axios.post(route("almacenes.store"), form.value);
        toastType.value = "success";
        toastMsg.value = data.message || "Almacén creado correctamente";
        isCreateModal.value = false;
        router.reload();
    } catch (e) {
        toastType.value = "danger";
        toastMsg.value =
            e.response?.data?.message ||
            Object.values(e.response?.data?.errors || {})
                .flat()
                .join(" ") ||
            "Error al crear almacén";
    } finally {
        loading.value = false;
        showToast.value = true;
        setTimeout(() => (showToast.value = false), 3000);
    }
}

// Edit via Axios
async function submitEdit() {
    if (loading.value || !selectedWarehouse.value) return;
    loading.value = true;
    try {
        const { data } = await axios.put(
            route("almacenes.update", selectedWarehouse.value.id),
            form.value
        );
        toastType.value = "success";
        toastMsg.value = data.message || "Almacén actualizado correctamente";
        isEditModal.value = false;
        router.reload();
    } catch (e) {
        toastType.value = "danger";
        toastMsg.value =
            e.response?.data?.message ||
            Object.values(e.response?.data?.errors || {})
                .flat()
                .join(" ") ||
            "Error al actualizar almacén";
    } finally {
        loading.value = false;
        showToast.value = true;
        setTimeout(() => (showToast.value = false), 3000);
    }
}

// Delete via Axios
async function submitDelete() {
    if (deleting.value || !selectedWarehouse.value) return;
    deleting.value = true;
    try {
        const { data } = await axios.post(
            route("almacenes.destroy", selectedWarehouse.value.id)
        );
        toastType.value = "success";
        toastMsg.value = data.message || "Almacén eliminado correctamente";
        isDeleteModal.value = false;
        router.reload();
    } catch (e) {
        toastType.value = "danger";
        toastMsg.value =
            e.response?.data?.message || "Error al eliminar almacén";
    } finally {
        deleting.value = false;
        showToast.value = true;
        setTimeout(() => (showToast.value = false), 3000);
    }
}
</script>

<template>
    <AdminLayout title="Almacenes">
        <!-- Toast -->
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType">{{
                toastMsg
            }}</FwbToast>
        </div>

        <!-- Header -->
        <div class="flex justify-between my-6">
            <h2 class="text-2xl font-semibold vet-page-title">
                <i class="fa-solid fa-warehouse mr-1 text-emerald-600"></i>
                <i class="fa-solid fa-warehouse mr-1 text-emerald-400"></i>
                <i class="fa-solid fa-warehouse text-emerald-200"></i>
                Almacenes
            </h2>
            <FwbButton v-if="canCreate" color="green" @click="openCreateModal">
                <i class="fa-solid fa-plus mr-2"></i> Agregar Almacén
            </FwbButton>
        </div>

        <!-- Table -->
        <FwbTable>
            <FwbTableHead>
                <FwbTableHeadCell>Nombre</FwbTableHeadCell>
                <FwbTableHeadCell>Ubicación</FwbTableHeadCell>
                <FwbTableHeadCell>Descripción</FwbTableHeadCell>
                <FwbTableHeadCell>Última modificación</FwbTableHeadCell>
                <FwbTableHeadCell
                    ><span class="sr-only">Acciones</span></FwbTableHeadCell
                >
            </FwbTableHead>
            <FwbTableBody>
                <FwbTableRow v-for="w in warehouses.data" :key="w.id">
                    <FwbTableCell>{{ w.name }}</FwbTableCell>
                    <FwbTableCell>{{ w.location }}</FwbTableCell>
                    <FwbTableCell>{{ w.description || "-" }}</FwbTableCell>
                    <FwbTableCell>{{ w.updated_at }}</FwbTableCell>
                    <FwbTableCell class="text-right">
                        <div class="inline-flex space-x-1 items-center">
                            <TableActionButtons
                                :can-view="true"
                                :can-edit="canEdit"
                                :can-delete="canEdit"
                                view-title="Ver inventario"
                                @view="router.get(route('almacenes.show', w.id))"
                                @edit="openEditModal(w)"
                                @delete="openDeleteModal(w)"
                            />
                        </div>
                    </FwbTableCell>
                </FwbTableRow>
            </FwbTableBody>
        </FwbTable>

        <!-- Pagination -->
        <div class="flex justify-center my-4">
            <FwbPagination
                v-model="currentPage"
                :total-items="warehouses.total"
                :per-page="warehouses.per_page"
                large
            />
        </div>

        <!-- Create Modal -->
        <FwbModal v-if="isCreateModal" @close="closeCreateModal">
            <template #header>Nuevo Almacén</template>
            <template #body>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm">Nombre</label
                        ><input
                            v-model="form.name"
                            class="w-full p-2 border rounded"
                        />
                    </div>
                    <div>
                        <label class="block text-sm">Ubicación</label
                        ><input
                            v-model="form.location"
                            class="w-full p-2 border rounded"
                        />
                    </div>
                    <div>
                        <label class="block text-sm">Descripción</label
                        ><textarea
                            v-model="form.description"
                            class="w-full p-2 border rounded"
                        ></textarea>
                    </div>
                </div>
            </template>
            <template #footer>
                <FwbButton
                    color="alternative"
                    @click="closeCreateModal"
                    :disabled="loading"
                    >Cancelar</FwbButton
                >
                <FwbButton
                    color="green"
                    @click="submitCreate"
                    :disabled="loading"
                    >Guardar</FwbButton
                >
            </template>
        </FwbModal>

        <!-- Edit Modal -->
        <FwbModal v-if="isEditModal" @close="closeEditModal">
            <template #header>Editar Almacén</template>
            <template #body>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm">Nombre</label
                        ><input
                            v-model="form.name"
                            class="w-full p-2 border rounded"
                        />
                    </div>
                    <div>
                        <label class="block text-sm">Ubicación</label
                        ><input
                            v-model="form.location"
                            class="w-full p-2 border rounded"
                        />
                    </div>
                    <div>
                        <label class="block text-sm">Descripción</label
                        ><textarea
                            v-model="form.description"
                            class="w-full p-2 border rounded"
                        ></textarea>
                    </div>
                </div>
            </template>
            <template #footer>
                <FwbButton
                    color="alternative"
                    @click="closeEditModal"
                    :disabled="loading"
                    >Cancelar</FwbButton
                >
                <FwbButton
                    color="green"
                    @click="submitEdit"
                    :disabled="loading"
                    >Actualizar</FwbButton
                >
            </template>
        </FwbModal>

        <!-- Delete Modal -->
        <FwbModal v-if="isDeleteModal" @close="closeDeleteModal">
            <template #header>Confirmar eliminación</template>
            <template #body>
                <div class="text-center">
                    <i
                        class="fa-solid fa-exclamation-triangle text-red-500 text-4xl mb-4"
                    ></i>
                    <p class="text-lg">
                        ¿Eliminar <strong>{{ selectedWarehouse.name }}</strong
                        >?
                    </p>
                    <p class="text-sm text-gray-600 mt-2">
                        Esta acción no se puede deshacer.
                    </p>
                </div>
            </template>
            <template #footer>
                <FwbButton
                    color="alternative"
                    @click="closeDeleteModal"
                    :disabled="deleting"
                    >Cancelar</FwbButton
                >
                <FwbButton
                    color="red"
                    @click="submitDelete"
                    :disabled="deleting"
                    >Eliminar</FwbButton
                >
            </template>
        </FwbModal>
    </AdminLayout>
</template>
