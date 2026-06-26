<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref, watch, computed, nextTick } from "vue";
import { usePage, Link, router } from "@inertiajs/vue3";
import {
    FwbA,
    FwbButton,
    FwbTable,
    FwbTableBody,
    FwbTableCell,
    FwbTableHead,
    FwbTableHeadCell,
    FwbTableRow,
    FwbPagination,
    FwbModal,
    FwbToast,
    FwbBadge,
} from "flowbite-vue";
import { usePermisos } from "@/Composables/usePermisos";

const { puede } = usePermisos();
const canCreate = computed(() => puede("crear inventario"));
const canEdit = computed(() => puede("editar inventario"));

const page = usePage();

// si no viene flash, lo convertimos en objeto vacío
const flash = computed(() => page.props.flash || {});

// detecta flash.success y flash.error
const showSuccessToast = ref(false);
const showErrorToast = ref(false);

// Watchers simplificados - solo para detectar flash messages
watch(
    () => flash.value.success,
    (val) => {
        if (val) {
            showSuccessToast.value = true;
            setTimeout(() => (showSuccessToast.value = false), 3000);
        }
    }
);

watch(
    () => flash.value.error,
    (val) => {
        if (val) {
            showErrorToast.value = true;
            setTimeout(() => (showErrorToast.value = false), 3000);
        }
    }
);

const props = defineProps({
    warehouse: Object,
    medicament: Object,
    inventories: Object,
});

const currentPage = ref(props.inventories.current_page || 1);

// Watch para manejar la paginación
watch(currentPage, (newPage) => {
    router.get(
        route("almacenes.inventario", {
            warehouseId: props.warehouse.id,
            medicamentId: props.medicament.id,
        }),
        { page: newPage },
        { preserveState: true }
    );
});

// Estados de modales
const isShowCreateModal = ref(false);
const isShowViewModal = ref(false);
const isShowEditModal = ref(false);
const isProcessing = ref(false);

const modalForm = ref({
    stock: null,
    price: null,
});

// Para “Ver” y “Editar”:
const selectedInventory = ref(null);

// Abrir modales
function openCreateModal() {
    selectedInventory.value = null;
    modalForm.value = { stock: null, price: null };
    isShowCreateModal.value = true;
}
function openViewModal(inv) {
    selectedInventory.value = inv;
    isShowViewModal.value = true;
}
function openEditModal(inv) {
    selectedInventory.value = inv;
    modalForm.value = { stock: inv.stock, price: inv.price };
    isShowEditModal.value = true;
}

const toastMessage = ref("");

function submitCreate() {
    if (isProcessing.value) return;
    isProcessing.value = true;

    router.post(
        route("almacenes.inventario.store", {
            warehouseId: props.warehouse.id,
            medicamentId: props.medicament.id,
        }),
        modalForm.value,
        {
            // si quieres que recargue y coja flash:
            // preserveState: false,
            onSuccess: () => {
                isShowCreateModal.value = false;
                toastMessage.value = "Lote agregado exitosamente";
                showSuccessToast.value = true;
            },
            onError: () => {
                toastMessage.value = "Error al agregar lote";
                showErrorToast.value = true;
            },
            onFinish: () => {
                isProcessing.value = false;
                // ocultar el toast al cabo de 3s
                setTimeout(() => (showSuccessToast.value = false), 3000);
                setTimeout(() => (showErrorToast.value = false), 3000);
            },
        }
    );
}

function submitEdit() {
    if (isProcessing.value) return;
    isProcessing.value = true;

    router.put(
        route("almacenes.inventario.update", {
            warehouseId: props.warehouse.id,
            medicamentId: props.medicament.id,
            inventoryId: selectedInventory.value.id,
        }),
        modalForm.value,
        {
            onSuccess: () => {
                isShowEditModal.value = false;
                toastMessage.value = "Lote actualizado correctamente";
                showSuccessToast.value = true;
            },
            onError: () => {
                toastMessage.value = "Error al actualizar lote";
                showErrorToast.value = true;
            },
            onFinish: () => {
                isProcessing.value = false;
                setTimeout(() => (showSuccessToast.value = false), 3000);
                setTimeout(() => (showErrorToast.value = false), 3000);
            },
        }
    );
}

// estados para eliminar
const isShowDeleteModal = ref(false);
const isDeleting = ref(false);

// abrir confirmación de borrar
function openDeleteModal(inv) {
    selectedInventory.value = inv;
    isShowDeleteModal.value = true;
}

// submitDelete - CON preserveState (solo para este caso problemático)
function submitDelete() {
    if (isDeleting.value) return;
    isDeleting.value = true;

    router.delete(
        route("almacenes.inventario.destroy", {
            warehouseId: props.warehouse.id,
            medicamentId: props.medicament.id,
            inventoryId: selectedInventory.value.id,
        }),
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                isShowDeleteModal.value = false;
                toastMessage.value = "Lote eliminado correctamente";
                showSuccessToast.value = true;
                setTimeout(() => (showSuccessToast.value = false), 3000);
            },
            onError: (errors) => {
                console.error("Error al eliminar:", errors);
                toastMessage.value = "Error al eliminar lote";
                showErrorToast.value = true;
                setTimeout(() => (showErrorToast.value = false), 3000);
            },
            onFinish: () => {
                isDeleting.value = false;
            },
        }
    );
}

console.log(props.inventories);
</script>

<template>
    <AdminLayout :title="`Inventarios de ${props.warehouse.name}`">
        <div class="flex justify-between my-6">
            <h2 class="text-2xl font-semibold vet-page-title">
                Inventarios (Lotes de medicamento {{ props.medicament.name }})
                de
                {{ props.warehouse.name }}
            </h2>
            <FwbButton v-if="canCreate" color="green" @click="openCreateModal">
                <i class="fa-solid fa-plus mr-2"></i> Agregar nuevo lote
            </FwbButton>
        </div>
        <div class="h-12">
            <FwbButton
                :href="route('almacenes.show', props.warehouse.id)"
                type="button"
                color="yellow"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="inline w-4 h-4 mr-2"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 19l-7-7 7-7"
                    />
                </svg>
                Volver a Almacén
            </FwbButton>
        </div>
        <div class="fixed top-40 right-6 z-50">
            <!-- Toasts -->
            <FwbToast v-if="showSuccessToast" type="success">
                {{ toastMessage }}
            </FwbToast>
            <FwbToast v-if="showErrorToast" type="danger">
                {{ toastMessage }}
            </FwbToast>
        </div>
        <div v-if="props.inventories && props.inventories.data.length > 0">
            <FwbTable>
                <FwbTableHead>
                    <FwbTableHeadCell>Medicamento</FwbTableHeadCell>
                    <FwbTableHeadCell>Stock</FwbTableHeadCell>
                    <FwbTableHeadCell>Precio</FwbTableHeadCell>
                    <FwbTableHeadCell>Almacén</FwbTableHeadCell>
                    <FwbTableHeadCell>Tipo de lote</FwbTableHeadCell>
                    <FwbTableHeadCell>
                        <span class="sr-only">Acciones</span>
                    </FwbTableHeadCell>
                </FwbTableHead>
                <FwbTableBody>
                    <FwbTableRow
                        v-for="(inventory, index) in props.inventories.data"
                        :key="index"
                    >
                        <FwbTableCell>
                            {{ inventory.medicament.name }}
                        </FwbTableCell>
                        <FwbTableCell>
                            {{ inventory.stock }}
                        </FwbTableCell>
                        <FwbTableCell> {{ inventory.price }}Bs. </FwbTableCell>
                        <FwbTableCell>
                            {{ inventory.warehouse.name }}
                        </FwbTableCell>
                        <FwbTableCell>
                            <fwb-badge
                                v-if="
                                    inventory.purchase_note_detail_id !== null
                                "
                                type="yellow"
                                >Auto</fwb-badge
                            >
                            <fwb-badge
                                v-if="
                                    inventory.purchase_note_detail_id === null
                                "
                                type="green"
                                >Manual</fwb-badge
                            >
                        </FwbTableCell>
                        <FwbTableCell>
                            <div class="flex space-x-2">
                                <FwbA
                                    @click.prevent="openViewModal(inventory)"
                                    class="p-1 rounded hover:bg-emerald-50"
                                >
                                    <i
                                        class="fa-solid fa-eye text-black hover:text-blue-600"
                                    ></i>
                                </FwbA>
                                <FwbA
                                    v-if="
                                        canEdit &&
                                        inventory.purchase_note_detail_id ===
                                            null
                                    "
                                    @click.prevent="openEditModal(inventory)"
                                    class="p-1 rounded hover:bg-emerald-50"
                                >
                                    <i
                                        class="fa-solid fa-pencil text-black hover:text-blue-600"
                                    ></i>
                                </FwbA>
                                <FwbA
                                    v-if="
                                        canEdit &&
                                        inventory.purchase_note_detail_id ===
                                            null
                                    "
                                    @click.prevent="openDeleteModal(inventory)"
                                    class="p-1 hover:bg-emerald-50 rounded"
                                >
                                    <i
                                        class="fa-solid fa-trash text-black hover:text-red-600"
                                    ></i>
                                </FwbA>
                            </div>
                        </FwbTableCell>
                    </FwbTableRow>
                </FwbTableBody>
            </FwbTable>
        </div>
        <div v-else>
            <p>No hay inventarios (Lotes de medicamento) para este almacén.</p>
        </div>
        <div class="flex justify-center my-4">
            <FwbPagination
                v-model="currentPage"
                :total-items="props.inventories.total"
                :per-page="props.inventories.per_page"
                large
            ></FwbPagination>
        </div>

        <!-- Mostrar detalle (solo lectura) -->
        <FwbModal v-if="isShowViewModal" @close="isShowViewModal = false">
            <template #header>Detalle de Lote</template>
            <template #body>
                <p>
                    <i class="fa-solid fa-warehouse mr-1"></i>
                    <strong>Almacén:</strong> {{ props.warehouse.name }}
                </p>
                <p>
                    <i class="fa-solid fa-capsules mr-1"></i>
                    <strong>Medicamento:</strong> {{ props.medicament.name }}
                </p>
                <p>
                    <i class="fa-solid fa-boxes-stacked mr-1"></i>
                    <strong>Stock:</strong> {{ selectedInventory.stock }}
                </p>
                <p>
                    <i class="fa-solid fa-money-bill-wave mr-1"></i>
                    <strong>Precio:</strong> {{ selectedInventory.price }} Bs.
                </p>
                <p>
                    <i class="fa-regular fa-calendar-plus mr-1"></i>
                    <strong>Creado:</strong> {{ selectedInventory.created_at }}
                </p>
            </template>
            <template #footer>
                <FwbButton color="alternative" @click="isShowViewModal = false">
                    Cerrar
                </FwbButton>
            </template>
        </FwbModal>

        <!-- Editar lote -->
        <FwbModal v-if="isShowEditModal" @close="isShowEditModal = false">
            <template #header>Editar Lote</template>
            <template #body>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold">
                            <i class="fa-solid fa-warehouse mr-1"></i>
                            Almacén
                        </label>
                        <p class="mt-1">{{ props.warehouse.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-bold">
                            <i class="fa-solid fa-capsules mr-1"></i>
                            Medicamento
                        </label>
                        <p class="mt-1">{{ props.medicament.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm">Stock</label>
                        <input
                            v-model.number="modalForm.stock"
                            type="number"
                            min="0"
                            class="w-full p-2 border rounded"
                        />
                    </div>
                    <div>
                        <label class="block text-sm">Precio</label>
                        <input
                            v-model.number="modalForm.price"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full p-2 border rounded"
                        />
                    </div>
                </div>
            </template>
            <template #footer>
                <FwbButton color="alternative" @click="isShowEditModal = false"
                    >Cancelar</FwbButton
                >
                <FwbButton
                    color="green"
                    @click="submitEdit"
                    :disabled="isProcessing"
                >
                    <i class="fa-solid fa-arrows-rotate mr-2"></i>
                    <span v-if="!isProcessing">Actualizar</span>
                    <span v-else>Actualizando…</span>
                </FwbButton>
            </template>
        </FwbModal>

        <!-- Modal de creación de lote -->
        <FwbModal v-if="isShowCreateModal" @close="isShowCreateModal = false">
            <template #header>
                <h3 class="text-lg font-semibold">Nuevo Lote</h3>
            </template>

            <template #body>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold">
                            <i class="fa-solid fa-warehouse mr-1"></i>
                            Almacén
                        </label>
                        <p class="mt-1">{{ props.warehouse.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-bold">
                            <i class="fa-solid fa-capsules mr-1"></i>
                            Medicamento
                        </label>
                        <p class="mt-1">{{ props.medicament.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Stock</label>
                        <input
                            v-model.number="modalForm.stock"
                            type="number"
                            min="1"
                            class="mt-1 w-full p-2 border rounded"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Precio</label>
                        <input
                            v-model.number="modalForm.price"
                            type="number"
                            step="0.01"
                            min="0"
                            class="mt-1 w-full p-2 border rounded"
                        />
                    </div>
                </div>
            </template>

            <template #footer>
                <div class="flex justify-end space-x-2">
                    <FwbButton
                        color="alternative"
                        @click="isShowCreateModal = false"
                    >
                        Cancelar
                    </FwbButton>
                    <FwbButton
                        color="green"
                        @click="submitCreate"
                        :disabled="isProcessing"
                    >
                        <i class="fa-solid fa-save mr-2"></i>
                        <span v-if="!isProcessing">Guardar</span>
                        <span v-else>Guardando...</span>
                    </FwbButton>
                </div>
            </template>
        </FwbModal>

        <!-- Confirmar eliminación -->
        <FwbModal v-if="isShowDeleteModal" @close="isShowDeleteModal = false">
            <template #header>Confirmar eliminación</template>
            <template #body>
                <p>
                    ¿Seguro que deseas eliminar el lote con
                    <strong>Stock: {{ selectedInventory.stock }}</strong> y
                    <strong>Precio: {{ selectedInventory.price }}</strong
                    >?
                </p>
            </template>
            <template #footer>
                <FwbButton
                    color="alternative"
                    @click="isShowDeleteModal = false"
                    :disabled="isDeleting"
                >
                    <i class="fa-solid fa-ban mr-2"></i>
                    Cancelar
                </FwbButton>
                <FwbButton
                    color="red"
                    @click="submitDelete"
                    :disabled="isDeleting"
                >
                    <i class="fa-solid fa-trash mr-2"></i>
                    <span v-if="!isDeleting">Eliminar</span>
                    <span v-else>Eliminando…</span>
                </FwbButton>
            </template>
        </FwbModal>
    </AdminLayout>
</template>
