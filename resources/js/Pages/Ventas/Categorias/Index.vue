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
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import TextInput from "@/Components/TextInput.vue";
import { useFormErrors } from "@/Composables/useFormErrors";

// Props
const props = defineProps({ categories: Object });
const { clearErrors, fromAxios, get } = useFormErrors();
const currentPage = ref(props.categories.current_page || 1);
watch(currentPage, (page) => {
    router.get(route("categorias.index"), { page }, { preserveState: true });
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

// Form & selected
const formCategory = ref({ name: "" });
const selected = ref(null);
const loading = ref(false);
const { puede } = usePermisos();
const canCreate = computed(() => puede("crear categorias"));
const canEdit = computed(() => puede("editar categorias"));

// Open modals
function openView(category) {
    selected.value = category;
    isViewModal.value = true;
}
function openCreate() {
    selected.value = null;
    formCategory.value = { name: "" };
    clearErrors();
    isCreateModal.value = true;
}
function openEdit(category) {
    selected.value = category;
    formCategory.value = { name: category.name };
    clearErrors();
    isEditModal.value = true;
}
function openDelete(category) {
    selected.value = category;
    isDeleteModal.value = true;
}

// CRUD actions
async function submitCreate() {
    loading.value = true;
    clearErrors();
    try {
        const { data } = await axios.post(
            route("categorias.store"),
            formCategory.value
        );
        toastType.value = "success";
        toastMsg.value =
            data.message || `Categoría "${data.name}" creada correctamente`;
        isCreateModal.value = false;
        router.reload();
    } catch (error) {
        toastType.value = "danger";
        toastMsg.value = fromAxios(error) || "Error al crear categoría";
    } finally {
        loading.value = false;
        showToast.value = true;
        setTimeout(() => (showToast.value = false), 3000);
    }
}

async function submitEdit() {
    loading.value = true;
    clearErrors();
    try {
        const { data } = await axios.put(
            route("categorias.update", selected.value.id),
            formCategory.value
        );
        toastType.value = "success";
        toastMsg.value = data.message || "Categoría actualizada correctamente";
        isEditModal.value = false;
        router.reload();
    } catch (error) {
        toastType.value = "danger";
        toastMsg.value = fromAxios(error) || "Error al actualizar categoría";
    } finally {
        loading.value = false;
        showToast.value = true;
        setTimeout(() => (showToast.value = false), 3000);
    }
}

async function submitDelete() {
    loading.value = true;
    try {
        const { data } = await axios.delete(
            route("categorias.destroy", selected.value.id)
        );
        toastType.value = "success";
        toastMsg.value = data.message || "Categoría eliminada correctamente";
        isDeleteModal.value = false;
        router.reload();
    } catch (error) {
        toastType.value = "danger";
        toastMsg.value =
            error.response?.data?.message || "Error al eliminar categoría";
    } finally {
        loading.value = false;
        showToast.value = true;
        setTimeout(() => (showToast.value = false), 3000);
    }
}
</script>

<template>
    <AdminLayout title="Categorías">
        <!-- Toast Notification -->
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType">{{
                toastMsg
            }}</FwbToast>
        </div>

        <!-- Header -->
        <div class="flex justify-between my-6">
            <h2 class="text-2xl font-semibold vet-page-title">Categorías</h2>
            <FwbButton v-if="canCreate" color="green" @click="openCreate">
                <i class="fa-solid fa-plus mr-2"></i> Nueva categoría
            </FwbButton>
        </div>

        <!-- Categories Table -->
        <FwbTable>
            <FwbTableHead>
                <FwbTableHeadCell>Nombre</FwbTableHeadCell>
                <FwbTableHeadCell>Última modificación</FwbTableHeadCell>
                <FwbTableHeadCell
                    ><span class="sr-only">Acciones</span></FwbTableHeadCell
                >
            </FwbTableHead>
            <FwbTableBody>
                <FwbTableRow
                    v-for="category in categories.data"
                    :key="category.id"
                >
                    <FwbTableCell>{{ category.name }}</FwbTableCell>
                    <FwbTableCell>{{ category.updated_at }}</FwbTableCell>
                    <FwbTableCell class="text-right">
                        <div class="inline-flex space-x-1">
                            <TableActionButtons
                                :can-view="true"
                                :can-edit="canEdit"
                                :can-delete="canEdit"
                                @view="openView(category)"
                                @edit="openEdit(category)"
                                @delete="openDelete(category)"
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
                :total-items="categories.total"
                :per-page="categories.per_page"
                large
            />
        </div>

        <!-- View Modal -->
        <FwbModal v-if="isViewModal" @close="isViewModal = false">
            <template #header>Detalle de Categoría</template>
            <template #body>
                <div class="space-y-2">
                    <p><strong>Nombre:</strong> {{ selected.name }}</p>
                    <p>
                        <strong>Última modificación:</strong>
                        {{ selected.updated_at }}
                    </p>
                </div>
            </template>
            <template #footer>
                <FwbButton color="alternative" @click="isViewModal = false">
                    Cerrar
                </FwbButton>
            </template>
        </FwbModal>

        <!-- Create Modal -->
        <FwbModal v-if="isCreateModal" @close="isCreateModal = false">
            <template #header>Nueva Categoría</template>
            <template #body>
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Nombre" />
                        <TextInput v-model="formCategory.name" class="w-full" />
                        <InputError :message="get('name')" />
                    </div>
                </div>
            </template>
            <template #footer>
                <FwbButton color="alternative" @click="isCreateModal = false">
                    Cancelar
                </FwbButton>
                <FwbButton
                    color="green"
                    @click="submitCreate"
                    :disabled="loading || !formCategory.name"
                >
                    <span v-if="!loading">Guardar</span>
                    <span v-else>Guardando…</span>
                </FwbButton>
            </template>
        </FwbModal>

        <!-- Edit Modal -->
        <FwbModal v-if="isEditModal" @close="isEditModal = false">
            <template #header>Editar Categoría</template>
            <template #body>
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Nombre" />
                        <TextInput v-model="formCategory.name" class="w-full" />
                        <InputError :message="get('name')" />
                    </div>
                </div>
            </template>
            <template #footer>
                <FwbButton color="alternative" @click="isEditModal = false">
                    Cancelar
                </FwbButton>
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
                        ¿Eliminar categoría
                        <strong>{{ selected.name }}</strong
                        >?
                    </p>
                </div>
            </template>
            <template #footer>
                <FwbButton color="alternative" @click="isDeleteModal = false">
                    Cancelar
                </FwbButton>
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
