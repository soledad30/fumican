<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref, watch, computed } from "vue";
import axios from "axios";
import { router } from "@inertiajs/vue3";
import { usePermisos } from "@/Composables/usePermisos";
import {
    FwbButton,
    FwbTable,
    FwbTableHead,
    FwbTableHeadCell,
    FwbTableBody,
    FwbTableRow,
    FwbTableCell,
    FwbPagination,
    FwbModal,
    FwbToast,
} from "flowbite-vue";
import TableActionButtons from "@/Components/TableActionButtons.vue";

// Props
const props = defineProps({ suppliers: Object });
const currentPage = ref(props.suppliers.current_page || 1);
// Watch for page changes
watch(currentPage, (page) => {
    router.get(route("proveedores.index"), { page }, { preserveState: true });
});

// Toast
const showToast = ref(false);
const toastMsg = ref("");
const toastType = ref("success");

// Modals
const isViewModal = ref(false);
const isCreateModal = ref(false);
const isEditModal = ref(false);
const isDeleteModal = ref(false);

// Form
const formSupplier = ref({
    name: "",
    country: "",
    phoneNumber: "",
    email: "",
    address: "",
});
const selected = ref(null);
const loading = ref(false);
const { puede } = usePermisos();
const canCreate = computed(() => puede("crear proveedores"));
const canEdit = computed(() => puede("editar proveedores"));

function openView(supplier) {
    selected.value = supplier;
    isViewModal.value = true;
}

// Open modals
function openCreate() {
    selected.value = null;
    formSupplier.value = {
        name: "",
        country: "",
        phoneNumber: "",
        email: "",
        address: "",
    };
    isCreateModal.value = true;
}

function openEdit(supplier) {
    selected.value = supplier;
    formSupplier.value = {
        name: supplier.name,
        country: supplier.country,
        phoneNumber: supplier.phoneNumber,
        email: supplier.email,
        address: supplier.address,
    };
    isEditModal.value = true;
}

function openDelete(supplier) {
    selected.value = supplier;
    isDeleteModal.value = true;
}

async function submitCreate() {
    loading.value = true;
    try {
        const response = await axios.post(
            route("proveedores.store"),
            formSupplier.value
        );
        toastType.value = "success";
        toastMsg.value =
            response.data.message ||
            `Proveedor "${response.data.name}" creado correctamente`;
        // Close modal on success only
        isCreateModal.value = false;
        router.reload();
    } catch (error) {
        toastType.value = "danger";
        if (error.response?.data?.errors) {
            toastMsg.value = Object.values(error.response.data.errors)
                .flat()
                .join(" ");
        } else if (error.response?.data?.message) {
            toastMsg.value = error.response.data.message;
        } else {
            toastMsg.value = "Error al crear proveedor";
        }
    } finally {
        loading.value = false;
        showToast.value = true;
        setTimeout(() => (showToast.value = false), 3000);
    }
}

async function submitEdit() {
    if (!selected.value) return;
    loading.value = true;
    try {
        const response = await axios.put(
            route("proveedores.update", selected.value.id),
            formSupplier.value
        );
        toastType.value = "success";
        toastMsg.value =
            response.data.message || "Proveedor actualizado correctamente";
        // Close modal on success only
        isEditModal.value = false;
        router.reload();
    } catch (error) {
        toastType.value = "danger";
        if (error.response?.data?.errors) {
            toastMsg.value = Object.values(error.response.data.errors)
                .flat()
                .join(" ");
        } else if (error.response?.data?.message) {
            toastMsg.value = error.response.data.message;
        } else {
            toastMsg.value = "Error al actualizar proveedor";
        }
    } finally {
        loading.value = false;
        showToast.value = true;
        setTimeout(() => (showToast.value = false), 3000);
    }
}

async function submitDelete() {
    if (!selected.value) return;
    loading.value = true;
    try {
        const response = await axios.delete(
            route("proveedores.destroy", selected.value.id)
        );
        toastType.value = "success";
        toastMsg.value =
            response.data.message || "Proveedor eliminado correctamente";
        router.reload();
    } catch (error) {
        toastType.value = "danger";
        toastMsg.value =
            error.response?.data?.message || "Error al eliminar proveedor";
    } finally {
        loading.value = false;
        showToast.value = true;
        isDeleteModal.value = false;
        setTimeout(() => (showToast.value = false), 3000);
    }
}
</script>

<template>
    <AdminLayout title="Proveedores">
        <!-- Toast Notification -->
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType">{{
                toastMsg
            }}</FwbToast>
        </div>

        <!-- Header -->
        <div class="flex justify-between my-6">
            <h2 class="text-2xl font-semibold vet-page-title">Proveedores</h2>
            <FwbButton v-if="canCreate" color="green" @click="openCreate">
                <i class="fa-solid fa-plus mr-2"></i> Nuevo proveedor
            </FwbButton>
        </div>

        <!-- Suppliers Table -->
        <FwbTable>
            <FwbTableHead>
                <FwbTableHeadCell>Nombre</FwbTableHeadCell>
                <FwbTableHeadCell>País</FwbTableHeadCell>
                <FwbTableHeadCell>Teléfono</FwbTableHeadCell>
                <FwbTableHeadCell>Email</FwbTableHeadCell>
                <FwbTableHeadCell>Dirección</FwbTableHeadCell>
                <FwbTableHeadCell
                    ><span class="sr-only">Acciones</span></FwbTableHeadCell
                >
            </FwbTableHead>
            <FwbTableBody>
                <FwbTableRow
                    v-for="supplier in suppliers.data"
                    :key="supplier.id"
                >
                    <FwbTableCell>{{ supplier.name }}</FwbTableCell>
                    <FwbTableCell>{{ supplier.country }}</FwbTableCell>
                    <FwbTableCell>{{ supplier.phoneNumber }}</FwbTableCell>
                    <FwbTableCell>{{ supplier.email }}</FwbTableCell>
                    <FwbTableCell>{{ supplier.address }}</FwbTableCell>
                    <FwbTableCell>
                        <TableActionButtons
                            :can-view="true"
                            :can-edit="canEdit"
                            :can-delete="canEdit"
                            @view="openView(supplier)"
                            @edit="openEdit(supplier)"
                            @delete="openDelete(supplier)"
                        />
                    </FwbTableCell>
                </FwbTableRow>
            </FwbTableBody>
        </FwbTable>

        <!-- Pagination -->
        <div class="flex justify-center my-4">
            <FwbPagination
                v-model="currentPage"
                :total-items="suppliers.total"
                :per-page="suppliers.per_page"
                large
            />
        </div>

        <!-- View Modal -->
        <FwbModal v-if="isViewModal" @close="isViewModal = false">
            <template #header>Detalle del Proveedor</template>
            <template #body>
                <div class="space-y-2">
                    <p><strong>Nombre:</strong> {{ selected.name }}</p>
                    <p><strong>País:</strong> {{ selected.country }}</p>
                    <p><strong>Teléfono:</strong> {{ selected.phoneNumber }}</p>
                    <p><strong>Email:</strong> {{ selected.email }}</p>
                    <p><strong>Dirección:</strong> {{ selected.address }}</p>
                </div>
            </template>
            <template #footer>
                <FwbButton color="alternative" @click="isViewModal = false"
                    >Cerrar</FwbButton
                >
            </template>
        </FwbModal>

        <!-- Create Modal -->
        <FwbModal v-if="isCreateModal" @close="isCreateModal = false">
            <template #header>Nuevo Proveedor</template>
            <template #body>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm">Nombre</label>
                        <input
                            v-model="formSupplier.name"
                            class="w-full p-2 border rounded"
                            required
                        />
                    </div>
                    <div>
                        <label class="block text-sm">País</label>
                        <input
                            v-model="formSupplier.country"
                            class="w-full p-2 border rounded"
                            required
                        />
                    </div>
                    <div>
                        <label class="block text-sm">Teléfono</label>
                        <input
                            v-model="formSupplier.phoneNumber"
                            class="w-full p-2 border rounded"
                            required
                        />
                    </div>
                    <div>
                        <label class="block text-sm">Email</label>
                        <input
                            v-model="formSupplier.email"
                            type="email"
                            class="w-full p-2 border rounded"
                            required
                        />
                    </div>
                    <div>
                        <label class="block text-sm">Dirección</label>
                        <input
                            v-model="formSupplier.address"
                            class="w-full p-2 border rounded"
                            required
                        />
                    </div>
                </div>
            </template>
            <template #footer>
                <FwbButton color="alternative" @click="isCreateModal = false"
                    >Cancelar</FwbButton
                >
                <FwbButton
                    color="green"
                    @click="submitCreate"
                    :disabled="loading ||
                        !formSupplier.name ||
                        !formSupplier.country ||
                        !formSupplier.phoneNumber ||
                        !formSupplier.email ||
                        !formSupplier.address"
                >
                    <span v-if="!loading">Guardar</span>
                    <span v-else>Guardando…</span>
                </FwbButton>
            </template>
        </FwbModal>

        <!-- Edit Modal -->
        <FwbModal v-if="isEditModal" @close="isEditModal = false">
            <template #header>Editar Proveedor</template>
            <template #body>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm">Nombre</label
                        ><input
                            v-model="formSupplier.name"
                            class="w-full p-2 border rounded"
                        />
                    </div>
                    <div>
                        <label class="block text-sm">País</label
                        ><input
                            v-model="formSupplier.country"
                            class="w-full p-2 border rounded"
                        />
                    </div>
                    <div>
                        <label class="block text-sm">Teléfono</label
                        ><input
                            v-model="formSupplier.phoneNumber"
                            class="w-full p-2 border rounded"
                        />
                    </div>
                    <div>
                        <label class="block text-sm">Email</label
                        ><input
                            v-model="formSupplier.email"
                            type="email"
                            class="w-full p-2 border rounded"
                        />
                    </div>
                    <div>
                        <label class="block text-sm">Dirección</label
                        ><input
                            v-model="formSupplier.address"
                            class="w-full p-2 border rounded"
                        />
                    </div>
                </div>
            </template>
            <template #footer>
                <FwbButton color="alternative" @click="isEditModal = false"
                    >Cancelar</FwbButton
                >
                <FwbButton
                    color="green"
                    @click="submitEdit"
                    :disabled="loading"
                >
                    <span v-if="!loading">Actualizar</span>
                    <span v-else>Actualizando…</span>
                </FwbButton>
            </template>
        </FwbModal>

        <!-- Delete Modal -->
        <FwbModal v-if="isDeleteModal" @close="isDeleteModal = false">
            <template #header>Confirmar Eliminación</template>
            <template #body>
                <div class="text-center space-y-4">
                    <i
                        class="fa-solid fa-exclamation-triangle text-red-500 text-4xl"
                    ></i>
                    <p>
                        ¿Eliminar proveedor <strong>{{ selected.name }}</strong
                        >?
                    </p>
                </div>
            </template>
            <template #footer>
                <FwbButton color="alternative" @click="isDeleteModal = false"
                    >Cancelar</FwbButton
                >
                <FwbButton
                    color="red"
                    @click="submitDelete"
                    :disabled="loading"
                >
                    <span v-if="!loading">Eliminar</span>
                    <span v-else>Eliminando…</span>
                </FwbButton>
            </template>
        </FwbModal>
    </AdminLayout>
</template>
