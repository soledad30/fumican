<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref, watch, computed } from "vue";
import axios from "axios";
import { router, usePage } from "@inertiajs/vue3";
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
    FwbBadge,
    FwbTabs,
    FwbTab,
} from "flowbite-vue";
import TableActionButtons from "@/Components/TableActionButtons.vue";

// Props
const props = defineProps({
    medicaments: Object,
    categories: Array,
    categoriesPaginated: Object,
    manufacturers: { type: Array, default: () => [] },
    warehouses: Array,
    unidadesMedida: { type: Object, default: () => ({}) },
    filters: Object,
});

const activeTab = ref("productos");
const categoryPage = ref(props.categoriesPaginated?.current_page || 1);

// Pagination
const currentPage = ref(props.medicaments.current_page);

// new:
watch(currentPage, (page) => {
    // always include filters + the new page
    const params = { ...filters.value, page };

    // hit the same 'search' endpoint even if filters are empty
    router.get(route("productos.search"), params, {
        preserveState: true,
        replace: true,
    });
});

// Toast
const showToast = ref(false);
const { puede } = usePermisos();
const canCreate = computed(() => puede("crear medicamentos"));
const canEdit = computed(() => puede("editar medicamentos"));
const canCreateCategory = computed(() => puede("crear categorias"));
const canEditCategory = computed(() => puede("editar categorias"));
const canInventario = computed(() => puede("crear inventario"));
const toastMsg = ref("");
const toastType = ref("success");

// Modals
const isViewModal = ref(false);
const isCreateModal = ref(false);
const isEditModal = ref(false);
const isDeleteModal = ref(false);
const isAddBatchModal = ref(false);

// State & form
const loading = ref(false);
const isBatchProcessing = ref(false);
const selectedMed = ref(null);
const selectedMedForBatch = ref(null);

const form = ref({
    name: "",
    unit: "unidad",
    presentation: "",
    dosage: "",
    manufacturer: "",
    expiration_date: "",
    controlled_substance: "no",
    category_id: null,
    min_stock: 0,
});

function etiquetaUnidad(unit) {
    return props.unidadesMedida[unit] ?? unit ?? "—";
}

function stockBajo(m) {
    const stock = Number(m.stock_total ?? 0);
    const minimo = Number(m.min_stock ?? m.stock_minimo ?? 0);
    return minimo > 0 && stock <= minimo;
}

const unidadesConDosificacion = ["comprimido", "capsula", "ml", "ampolla", "tableta"];

function requiereDosificacion(unit) {
    return unidadesConDosificacion.includes(unit ?? "unidad");
}

const batchForm = ref({
    warehouse_id: null,
    stock: null,
    price: null,
});

const formCategory = ref({ name: "" });
const selectedCategory = ref(null);
const isCategoryModal = ref(false);
const isCategoryDeleteModal = ref(false);

function categoriaNombre(item) {
    return item?.categoria?.name ?? item?.categoria?.nombre ?? item?.category?.name ?? "—";
}

function openCreateCategory() {
    selectedCategory.value = null;
    formCategory.value = { name: "" };
    isCategoryModal.value = true;
}

function openEditCategory(c) {
    selectedCategory.value = c;
    formCategory.value = { name: c.name };
    isCategoryModal.value = true;
}

function openDeleteCategory(c) {
    selectedCategory.value = c;
    isCategoryDeleteModal.value = true;
}

async function submitCategory() {
    loading.value = true;
    try {
        const url = selectedCategory.value
            ? route("categorias.update", selectedCategory.value.id)
            : route("categorias.store");
        const method = selectedCategory.value ? axios.put : axios.post;
        const { data } = await method(url, formCategory.value);
        toastType.value = "success";
        toastMsg.value = data.message;
        isCategoryModal.value = false;
        router.reload();
    } catch (e) {
        toastType.value = "danger";
        toastMsg.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {}).flat().join(" ") || "Error al guardar categoría";
    } finally {
        loading.value = false;
        showToast.value = true;
        setTimeout(() => (showToast.value = false), 3000);
    }
}

async function submitDeleteCategory() {
    if (!selectedCategory.value) return;
    loading.value = true;
    try {
        const { data } = await axios.delete(route("categorias.destroy", selectedCategory.value.id));
        toastType.value = "success";
        toastMsg.value = data.message;
        isCategoryDeleteModal.value = false;
        router.reload();
    } catch (e) {
        toastType.value = "danger";
        toastMsg.value = e.response?.data?.message || "Error al eliminar categoría";
    } finally {
        loading.value = false;
        showToast.value = true;
        setTimeout(() => (showToast.value = false), 3000);
    }
}

// Open modals
function openViewModal(m) {
    selectedMed.value = m;
    isViewModal.value = true;
}
function openCreateModal() {
    selectedMed.value = null;
    Object.assign(form.value, {
        name: "",
        unit: "unidad",
        presentation: "",
        dosage: "",
        manufacturer: "",
        expiration_date: "",
        controlled_substance: "no",
        category_id: null,
        min_stock: 0,
    });
    isCreateModal.value = true;
}
function openEditModal(m) {
    selectedMed.value = m;
    Object.assign(form.value, {
        name: m.name,
        unit: m.unit ?? m.unidad_medida ?? "unidad",
        presentation: m.presentation ?? m.presentacion ?? "",
        dosage: m.dosage,
        manufacturer: m.manufacturer,
        expiration_date: m.expiration_date ?? "",
        controlled_substance: m.controlled_substance,
        category_id: m.categoria?.id ?? m.category_id,
        min_stock: m.min_stock ?? m.stock_minimo ?? 0,
    });
    isEditModal.value = true;
}
function openDeleteModal(m) {
    selectedMed.value = m;
    isDeleteModal.value = true;
}
function openAddBatchModal(m) {
    selectedMedForBatch.value = m;
    Object.assign(batchForm.value, {
        warehouse_id: null,
        stock: null,
        price: null,
    });
    isAddBatchModal.value = true;
}
function closeAddBatchModal() {
    isAddBatchModal.value = false;
    selectedMedForBatch.value = null;
    isBatchProcessing.value = false;
    Object.assign(batchForm.value, {
        warehouse_id: null,
        stock: null,
        price: null,
    });
}

// CRUD via Axios
async function submitCreate() {
    loading.value = true;
    try {
        const { data } = await axios.post(
            route("productos.store"),
            form.value
        );
        toastType.value = "success";
        toastMsg.value = data.message || "Medicamento creado correctamente";
        isCreateModal.value = false;
        router.reload();
    } catch (e) {
        toastType.value = "danger";
        toastMsg.value =
            e.response?.data?.message ||
            Object.values(e.response?.data?.errors || {})
                .flat()
                .join(" ") ||
            "Error al crear medicamento";
    } finally {
        loading.value = false;
        showToast.value = true;
        setTimeout(() => (showToast.value = false), 3000);
    }
}

async function submitEdit() {
    if (!selectedMed.value) return;
    loading.value = true;
    try {
        const { data } = await axios.put(
            route("productos.update", selectedMed.value.id),
            form.value
        );
        toastType.value = "success";
        toastMsg.value =
            data.message || "Medicamento actualizado correctamente";
        isEditModal.value = false;
        router.reload();
    } catch (e) {
        toastType.value = "danger";
        toastMsg.value =
            e.response?.data?.message ||
            Object.values(e.response?.data?.errors || {})
                .flat()
                .join(" ") ||
            "Error al actualizar medicamento";
    } finally {
        loading.value = false;
        showToast.value = true;
        setTimeout(() => (showToast.value = false), 3000);
    }
}

async function submitDelete() {
    if (!selectedMed.value) return;
    loading.value = true;
    try {
        const { data } = await axios.delete(
            route("productos.destroy", selectedMed.value.id)
        );
        toastType.value = "success";
        toastMsg.value = data.message || "Medicamento eliminado correctamente";
        isDeleteModal.value = false;
        router.reload();
    } catch (e) {
        toastType.value = "danger";
        toastMsg.value =
            e.response?.data?.message || "Error al eliminar medicamento";
    } finally {
        loading.value = false;
        showToast.value = true;
        setTimeout(() => (showToast.value = false), 3000);
    }
}

// Batch via Inertia (mantener)
function submitAddBatch() {
    if (isBatchProcessing.value) return;
    if (!batchForm.value.warehouse_id) {
        toastType.value = "danger";
        toastMsg.value = "Debe seleccionar un almacén";
        showToast.value = true;
        return;
    }
    if (!batchForm.value.stock || batchForm.value.stock < 1) {
        toastType.value = "danger";
        toastMsg.value = "Debe ingresar un stock válido";
        showToast.value = true;
        return;
    }
    if (batchForm.value.price === null || batchForm.value.price < 0) {
        toastType.value = "danger";
        toastMsg.value = "Debe ingresar un precio válido";
        showToast.value = true;
        return;
    }

    isBatchProcessing.value = true;
    router.post(
        route("almacenes.inventario.store", {
            warehouseId: batchForm.value.warehouse_id,
            medicamentId: selectedMedForBatch.value.id,
        }),
        { stock: batchForm.value.stock, price: batchForm.value.price },
        {
            onSuccess: () => {
                closeAddBatchModal();
                toastType.value = "success";
                toastMsg.value = "Lote agregado correctamente";
                showToast.value = true;
            },
            onError: () => {
                toastType.value = "danger";
                toastMsg.value = "Error al agregar lote";
                showToast.value = true;
            },
            onFinish: () => {
                isBatchProcessing.value = false;
                setTimeout(() => (showToast.value = false), 3000);
            },
        }
    );
}

// grab any existing filters from server props
const page = usePage();
const initialFilters = page.props.filters || {};

const filters = ref({
    name: initialFilters.name || "",
    dosage: initialFilters.dosage || "",
    manufacturer: initialFilters.manufacturer || "",
    expiration_from: initialFilters.expiration_from || "",
    expiration_to: initialFilters.expiration_to || "",
    controlled_substance: initialFilters.controlled_substance || "",
    category_id: initialFilters.category_id || "",
    per_page: initialFilters.per_page || 15,
});

function applyFilters() {
    router.get(route("productos.search"), filters.value, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    Object.assign(filters.value, {
        name: "",
        dosage: "",
        manufacturer: "",
        expiration_from: "",
        expiration_to: "",
        controlled_substance: "",
        category_id: "",
        per_page: 15,
    });
    router.get(
        route("productos.index"),
        {},
        {
            preserveState: true,
            replace: true,
        }
    );
}
</script>

<template>
    <AdminLayout title="Productos">
        <!-- Toast -->
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType">{{
                toastMsg
            }}</FwbToast>
        </div>

        <div class="flex justify-between my-6 items-center">
            <h2 class="text-2xl font-semibold vet-page-title">Productos y categorías</h2>
        </div>

        <FwbTabs v-model="activeTab" variant="underline" class="mb-6">
            <FwbTab name="productos" title="Productos / Medicamentos">
        <!-- Header acciones productos -->
        <div class="flex justify-between my-4 flex-wrap gap-2">
            <div class="flex gap-2">
                <FwbButton v-if="canCreate" color="green" @click="openCreateModal">
                    <i class="fa-solid fa-plus mr-2"></i> Nuevo producto
                </FwbButton>
                <FwbButton
                    tag="a"
                    :href="route('productos.report', filters)"
                    target="_blank"
                    color="alternative"
                >
                    <i class="fa-solid fa-file-pdf mr-2"></i> PDF
                </FwbButton>
            </div>
        </div>

        <!-- Filter bar -->
        <form
            @submit.prevent="applyFilters"
            class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 vet-filter-panel p-4"
        >
            <div>
                <label class="block text-sm">Nombre</label>
                <input
                    v-model="filters.name"
                    type="text"
                    class="w-full p-2 border rounded"
                    placeholder="Buscar nombre…"
                />
            </div>
            <div>
                <label class="block text-sm">Dosificación</label>
                <input
                    v-model="filters.dosage"
                    type="text"
                    class="w-full p-2 border rounded"
                    placeholder="Buscar dosificación…"
                />
            </div>
            <div>
                <label class="block text-sm">Fabricante</label>
                <select
                    v-model="filters.manufacturer"
                    class="w-full p-2 border rounded"
                >
                    <option value="">Todos</option>
                    <option
                        v-for="fab in manufacturers"
                        :key="fab"
                        :value="fab"
                    >
                        {{ fab }}
                    </option>
                </select>
            </div>
            <div>
                <label class="block text-sm">Categoría</label>
                <select
                    v-model="filters.category_id"
                    class="w-full p-2 border rounded"
                >
                    <option value="">Todas</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">
                        {{ c.name }}
                    </option>
                </select>
            </div>

            <div>
                <label class="block text-sm">Expiración desde</label>
                <input
                    v-model="filters.expiration_from"
                    type="date"
                    class="w-full p-2 border rounded"
                />
            </div>
            <div>
                <label class="block text-sm">Expiración hasta</label>
                <input
                    v-model="filters.expiration_to"
                    type="date"
                    class="w-full p-2 border rounded"
                />
            </div>
            <div>
                <label class="block text-sm">Controlada</label>
                <select
                    v-model="filters.controlled_substance"
                    class="w-full p-2 border rounded"
                >
                    <option value="">Todas</option>
                    <option value="yes">Sí</option>
                    <option value="no">No</option>
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <FwbButton color="green" type="submit">Filtrar</FwbButton>
                <FwbButton color="alternative" @click.prevent="resetFilters"
                    >Limpiar</FwbButton
                >
            </div>
        </form>

        <!-- Tabla -->
        <FwbTable>
            <FwbTableHead>
                <FwbTableHeadCell>Nombre</FwbTableHeadCell>
                <FwbTableHeadCell>Unidad</FwbTableHeadCell>
                <FwbTableHeadCell>Stock</FwbTableHeadCell>
                <FwbTableHeadCell>P. venta ref.</FwbTableHeadCell>
                <FwbTableHeadCell>Dosificación</FwbTableHeadCell>
                <FwbTableHeadCell>Fabricante</FwbTableHeadCell>
                <FwbTableHeadCell>Categoría</FwbTableHeadCell>
                <FwbTableHeadCell
                    ><span class="sr-only">Acciones</span></FwbTableHeadCell
                >
            </FwbTableHead>
            <FwbTableBody>
                <FwbTableRow v-for="m in medicaments.data" :key="m.id">
                    <FwbTableCell>{{ m.name }}</FwbTableCell>
                    <FwbTableCell>{{ etiquetaUnidad(m.unit ?? m.unidad_medida) }}</FwbTableCell>
                    <FwbTableCell>
                        <FwbBadge :type="stockBajo(m) ? 'red' : 'green'">
                            {{ Number(m.stock_total ?? 0) }}
                        </FwbBadge>
                    </FwbTableCell>
                    <FwbTableCell>
                        {{ m.reference_sale_price != null ? Number(m.reference_sale_price).toFixed(2) + ' Bs' : '—' }}
                    </FwbTableCell>
                    <FwbTableCell>{{ m.dosage || "—" }}</FwbTableCell>
                    <FwbTableCell>{{ m.manufacturer }}</FwbTableCell>
                    <FwbTableCell>{{ categoriaNombre(m) }}</FwbTableCell>
                    <FwbTableCell class="text-right">
                        <div class="inline-flex space-x-1">
                            <FwbA
                                v-if="canInventario"
                                @click.prevent="openAddBatchModal(m)"
                                class="p-1 rounded hover:bg-emerald-50"
                            >
                                <i class="fa-solid fa-warehouse text-black"></i>
                            </FwbA>
                            <TableActionButtons
                                :can-view="true"
                                :can-edit="canEdit"
                                :can-delete="canEdit"
                                @view="openViewModal(m)"
                                @edit="openEditModal(m)"
                                @delete="openDeleteModal(m)"
                            />
                        </div>
                    </FwbTableCell>
                </FwbTableRow>
            </FwbTableBody>
        </FwbTable>

        <!-- Paginación -->
        <div class="flex justify-center my-4">
            <FwbPagination
                v-model="currentPage"
                :total-items="medicaments.total"
                :per-page="medicaments.per_page"
                large
            />
        </div>
            </FwbTab>

            <FwbTab name="categorias" title="Categorías">
                <div class="flex justify-between my-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Clasificación de productos veterinarios</p>
                    <FwbButton v-if="canCreateCategory" color="green" @click="openCreateCategory">
                        <i class="fa-solid fa-plus mr-2"></i> Nueva categoría
                    </FwbButton>
                </div>
                <FwbTable>
                    <FwbTableHead>
                        <FwbTableHeadCell>ID</FwbTableHeadCell>
                        <FwbTableHeadCell>Nombre</FwbTableHeadCell>
                        <FwbTableHeadCell>Actualizado</FwbTableHeadCell>
                        <FwbTableHeadCell><span class="sr-only">Acciones</span></FwbTableHeadCell>
                    </FwbTableHead>
                    <FwbTableBody>
                        <FwbTableRow v-if="!categoriesPaginated?.data?.length">
                            <FwbTableCell colspan="4" class="text-center text-gray-500 py-4">No hay categorías registradas.</FwbTableCell>
                        </FwbTableRow>
                        <FwbTableRow v-for="c in categoriesPaginated?.data || []" :key="c.id">
                            <FwbTableCell>{{ c.id }}</FwbTableCell>
                            <FwbTableCell>{{ c.name }}</FwbTableCell>
                            <FwbTableCell>{{ c.updated_at || c.actualizado_en || "—" }}</FwbTableCell>
                            <FwbTableCell>
                                <TableActionButtons
                                    :can-edit="canEditCategory"
                                    :can-delete="canEditCategory"
                                    @edit="openEditCategory(c)"
                                    @delete="openDeleteCategory(c)"
                                />
                            </FwbTableCell>
                        </FwbTableRow>
                    </FwbTableBody>
                </FwbTable>
                <div v-if="categoriesPaginated?.last_page > 1" class="flex justify-center my-4">
                    <FwbPagination v-model="categoryPage" :total-pages="categoriesPaginated.last_page" />
                </div>
            </FwbTab>
        </FwbTabs>

        <!-- View Modal -->
        <FwbModal v-if="isViewModal" @close="isViewModal = false">
            <template #header>Detalle de Medicamento</template>
            <template #body>
                <div class="space-y-2">
                    <p><strong>Nombre:</strong> {{ selectedMed.name }}</p>
                    <p><strong>Unidad:</strong> {{ etiquetaUnidad(selectedMed.unit ?? selectedMed.unidad_medida) }}</p>
                    <p v-if="selectedMed.presentation || selectedMed.presentacion">
                        <strong>Presentación:</strong> {{ selectedMed.presentation ?? selectedMed.presentacion }}
                    </p>
                    <p><strong>Stock total:</strong> {{ selectedMed.stock_total ?? 0 }}</p>
                    <p v-if="selectedMed.reference_sale_price != null">
                        <strong>Precio venta ref.:</strong> {{ Number(selectedMed.reference_sale_price).toFixed(2) }} Bs
                    </p>
                    <p>
                        <strong>Dosificación:</strong> {{ selectedMed.dosage || "No aplica" }}
                    </p>
                    <p>
                        <strong>Fabricante:</strong>
                        {{ selectedMed.manufacturer }}
                    </p>
                    <p>
                        <strong>Expiración:</strong>
                        {{ selectedMed.expiration_date }}
                    </p>
                    <p>
                        <strong>Controlada:</strong>
                        {{
                            selectedMed.controlled_substance === "yes"
                                ? "Sí"
                                : "No"
                        }}
                    </p>
                    <p>
                        <strong>Categoría:</strong>
                        {{ categoriaNombre(selectedMed) }}
                    </p>
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
            <template #header>Nuevo Medicamento</template>
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
                        <label class="block text-sm">Unidad de medida</label>
                        <select v-model="form.unit" class="w-full p-2 border rounded">
                            <option v-for="(label, key) in unidadesMedida" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm">Presentación</label>
                        <input v-model="form.presentation" placeholder="Ej. Caja x 10 comp." class="w-full p-2 border rounded" />
                    </div>
                    <div>
                        <label class="block text-sm">
                            Dosificación
                            <span v-if="!requiereDosificacion(form.unit)" class="text-gray-500 font-normal">(opcional — venta por unidad)</span>
                        </label>
                        <input
                            v-model="form.dosage"
                            :placeholder="requiereDosificacion(form.unit) ? 'Ej. 1 comp / 12 h' : 'No aplica'"
                            class="w-full p-2 border rounded"
                        />
                    </div>
                    <div>
                        <label class="block text-sm">Fabricante</label
                        ><input
                            v-model="form.manufacturer"
                            class="w-full p-2 border rounded"
                        />
                    </div>
                    <div>
                        <label class="block text-sm">Expiración referencial (opcional)</label
                        ><input
                            type="date"
                            v-model="form.expiration_date"
                            class="w-full p-2 border rounded"
                        />
                    </div>
                    <div>
                        <label class="block text-sm">Stock mínimo (alerta)</label>
                        <input v-model.number="form.min_stock" type="number" min="0" class="w-full p-2 border rounded" />
                    </div>
                    <div>
                        <label class="block text-sm">Controlada</label
                        ><select
                            v-model="form.controlled_substance"
                            class="w-full p-2 border rounded"
                        >
                            <option value="no">No</option>
                            <option value="yes">Sí</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm">Categoría</label
                        ><select
                            v-model="form.category_id"
                            class="w-full p-2 border rounded"
                        >
                            <option disabled value="">Seleccionar</option>
                            <option
                                v-for="c in categories"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }}
                            </option>
                        </select>
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
                    :disabled="loading"
                    >Guardar</FwbButton
                >
            </template>
        </FwbModal>

        <!-- Edit Modal -->
        <FwbModal v-if="isEditModal" @close="isEditModal = false">
            <template #header>Editar Medicamento</template>
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
                        <label class="block text-sm">Unidad de medida</label>
                        <select v-model="form.unit" class="w-full p-2 border rounded">
                            <option v-for="(label, key) in unidadesMedida" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm">Presentación</label>
                        <input v-model="form.presentation" class="w-full p-2 border rounded" />
                    </div>
                    <div>
                        <label class="block text-sm">
                            Dosificación
                            <span v-if="!requiereDosificacion(form.unit)" class="text-gray-500 font-normal">(opcional — venta por unidad)</span>
                        </label>
                        <input
                            v-model="form.dosage"
                            :placeholder="requiereDosificacion(form.unit) ? 'Ej. 1 comp / 12 h' : 'No aplica'"
                            class="w-full p-2 border rounded"
                        />
                    </div>
                    <div>
                        <label class="block text-sm">Fabricante</label
                        ><input
                            v-model="form.manufacturer"
                            class="w-full p-2 border rounded"
                        />
                    </div>
                    <div>
                        <label class="block text-sm">Expiración referencial (opcional)</label
                        ><input
                            type="date"
                            v-model="form.expiration_date"
                            class="w-full p-2 border rounded"
                        />
                    </div>
                    <div>
                        <label class="block text-sm">Stock mínimo (alerta)</label>
                        <input v-model.number="form.min_stock" type="number" min="0" class="w-full p-2 border rounded" />
                    </div>
                    <div>
                        <label class="block text-sm">Controlada</label
                        ><select
                            v-model="form.controlled_substance"
                            class="w-full p-2 border rounded"
                        >
                            <option value="no">No</option>
                            <option value="yes">Sí</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm">Categoría</label
                        ><select
                            v-model="form.category_id"
                            class="w-full p-2 border rounded"
                        >
                            <option disabled value="">Seleccionar</option>
                            <option
                                v-for="c in categories"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }}
                            </option>
                        </select>
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
                    >Actualizar</FwbButton
                >
            </template>
        </FwbModal>

        <!-- Delete Modal -->
        <FwbModal v-if="isDeleteModal" @close="isDeleteModal = false">
            <template #header>Confirmar eliminación</template>
            <template #body>
                <div class="text-center">
                    <i
                        class="fa-solid fa-exclamation-triangle text-red-500 text-4xl mb-4"
                    ></i>
                    <p class="text-lg">
                        ¿Eliminar <strong>{{ selectedMed.name }}</strong
                        >?
                    </p>
                    <p class="text-sm text-gray-600 mt-2">
                        Esta acción no se puede deshacer.
                    </p>
                </div>
            </template>
            <template #footer>
                <FwbButton color="alternative" @click="isDeleteModal = false"
                    >Cancelar</FwbButton
                >
                <FwbButton color="red" @click="submitDelete" :disabled="loading"
                    >Eliminar</FwbButton
                >
            </template>
        </FwbModal>

        <!-- Add Batch Modal -->
        <FwbModal v-if="isAddBatchModal" @close="closeAddBatchModal">
            <template #header
                >Agregar lote a: {{ selectedMedForBatch.name }}</template
            >
            <template #body>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1"
                            >Almacén</label
                        ><select
                            v-model="batchForm.warehouse_id"
                            class="w-full p-2 border rounded"
                        >
                            <option disabled value="">
                                Seleccionar almacén
                            </option>
                            <option
                                v-for="w in warehouses"
                                :key="w.id"
                                :value="w.id"
                            >
                                {{ w.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1"
                            >Stock</label
                        ><input
                            type="number"
                            v-model.number="batchForm.stock"
                            min="1"
                            class="w-full p-2 border rounded"
                            placeholder="Cantidad de unidades"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1"
                            >Precio</label
                        ><input
                            type="number"
                            v-model.number="batchForm.price"
                            min="0"
                            step="0.01"
                            class="w-full p-2 border rounded"
                            placeholder="Precio por unidad"
                        />
                    </div>
                </div>
            </template>
            <template #footer>
                <FwbButton color="alternative" @click="closeAddBatchModal"
                    >Cancelar</FwbButton
                >
                <FwbButton
                    color="green"
                    @click="submitAddBatch"
                    :disabled="isBatchProcessing"
                    >Agregar lote</FwbButton
                >
            </template>
        </FwbModal>

        <FwbModal v-if="isCategoryModal" @close="isCategoryModal = false">
            <template #header>{{ selectedCategory ? "Editar" : "Nueva" }} categoría</template>
            <template #body>
                <label class="block text-sm font-medium mb-1">Nombre</label>
                <input v-model="formCategory.name" type="text" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600" maxlength="100" />
            </template>
            <template #footer>
                <FwbButton color="alternative" @click="isCategoryModal = false">Cancelar</FwbButton>
                <FwbButton color="green" :disabled="loading" @click="submitCategory">Guardar</FwbButton>
            </template>
        </FwbModal>

        <FwbModal v-if="isCategoryDeleteModal" @close="isCategoryDeleteModal = false">
            <template #header>Confirmar eliminación</template>
            <template #body>¿Eliminar la categoría "{{ selectedCategory?.name }}"?</template>
            <template #footer>
                <FwbButton color="alternative" @click="isCategoryDeleteModal = false">Cancelar</FwbButton>
                <FwbButton color="red" :disabled="loading" @click="submitDeleteCategory">Eliminar</FwbButton>
            </template>
        </FwbModal>
    </AdminLayout>
</template>
