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
    FwbPagination,
    FwbModal,
    FwbToast,
} from "flowbite-vue";
import TableActionButtons from "@/Components/TableActionButtons.vue";

// Props desde el backend
const props = defineProps({
    purchases: Object,
    suppliers: Array,
    warehouses: Array,
    filters: Object,
});

// Estado reactivo para paginación y filtros
const currentPage = ref(props.purchases.current_page);
const filters = ref({
    supplier_id: props.filters?.supplier_id || "",
    warehouse_id: props.filters?.warehouse_id || "",
    date_from: props.filters?.date_from || "",
    date_to: props.filters?.date_to || "",
    per_page: props.filters?.per_page || 15,
});

// Watch para paginación
watch(currentPage, (page) => {
    router.get(
        route("notas-compra.search"),
        { ...filters.value, page },
        {
            preserveState: true,
            replace: true,
        }
    );
});

// Toast
const showToast = ref(false);
const { puede } = usePermisos();
const canCreate = computed(() => puede("crear notas de compras"));
const canEdit = computed(() => puede("editar notas de compras"));
const toastMsg = ref("");
const toastType = ref("success");

// Delete Modal
const isDeleteModal = ref(false);
const deleteTarget = ref(null);

function openDeleteModal(p) {
    deleteTarget.value = p;
    isDeleteModal.value = true;
}

async function submitDelete() {
    try {
        await axios.delete(route("notas-compra.destroy", deleteTarget.value.id));
        toastType.value = "success";
        toastMsg.value = "Nota de compra eliminada correctamente";
        isDeleteModal.value = false;
        router.reload();
    } catch (e) {
        toastType.value = "danger";
        toastMsg.value = "Error al eliminar";
    } finally {
        showToast.value = true;
        setTimeout(() => (showToast.value = false), 2500);
    }
}

// Filtros
function applyFilters() {
    router.get(route("notas-compra.search"), filters.value, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    Object.assign(filters.value, {
        supplier_id: "",
        warehouse_id: "",
        date_from: "",
        date_to: "",
        per_page: 15,
    });
    router.get(
        route("notas-compra.index"),
        {},
        {
            preserveState: true,
            replace: true,
        }
    );
}

// --- View Modal (detalle de compra) ---
const isShowModal = ref(false);
const selectedPurchase = ref(null);
const selectedDetails = ref([]);

function nombreProveedor(registro) {
    const p = registro?.proveedor ?? registro?.supplier;
    return p?.name ?? p?.nombre ?? "—";
}

function nombreAlmacen(registro) {
    const a = registro?.almacen ?? registro?.warehouse;
    return a?.name ?? a?.nombre ?? "—";
}

function nombreProducto(detalle) {
    const p = detalle?.producto ?? detalle?.medicament;
    return p?.name ?? p?.nombre ?? "—";
}

async function viewPurchase(p) {
    try {
        const { data } = await axios.get(route("notas-compra.show", p.id), {
            headers: { Accept: "application/json" },
        });
        selectedPurchase.value = data.purchaseNote;
        selectedDetails.value = data.purchaseNoteDetails;
        isShowModal.value = true;
    } catch {
        toastType.value = "danger";
        toastMsg.value = "Error cargando detalle";
        showToast.value = true;
        setTimeout(() => (showToast.value = false), 2500);
    }
}

// --- Imprimir PDF individual (por compra) ---
// IMPORTANTE: Tu ruta definida es 'purchase.pdf', pero el endpoint es 'purchases/purchases/{id}/pdf'
function printPurchase(p) {
    // Así funciona con la definición de tu ruta:
    window.open(route("notas-compra.pdf", { id: p.id }), "_blank");
}
</script>

<template>
    <AdminLayout title="Notas de Compra">
        <!-- Toast -->
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType">{{
                toastMsg
            }}</FwbToast>
        </div>

        <div class="flex justify-between items-center my-6">
            <h2 class="text-2xl font-semibold vet-page-title">Notas de Compra</h2>
            <div class="space-x-2">
                <FwbButton
                    v-if="canCreate"
                    color="green"
                    @click="router.get(route('notas-compra.create'))"
                >
                    <i class="fa-solid fa-plus mr-2"></i> Nueva Nota
                </FwbButton>
                <FwbButton
                    tag="a"
                    :href="
                        route('notas-compra.report', {
                            supplier_id: filters.supplier_id,
                            warehouse_id: filters.warehouse_id,
                            date_from: filters.date_from,
                            date_to: filters.date_to,
                        })
                    "
                    target="_blank"
                    color="green"
                    class="ml-4"
                >
                    <i class="fa-solid fa-file-pdf mr-2"></i> Generar PDF
                </FwbButton>
            </div>
        </div>

        <!-- Barra de Filtros -->
        <form
            @submit.prevent="applyFilters"
            class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6 bg-white p-4 rounded shadow"
        >
            <div>
                <label class="block text-sm">Proveedor</label>
                <select
                    v-model="filters.supplier_id"
                    class="w-full p-2 border rounded"
                >
                    <option value="">Todos</option>
                    <option v-for="s in suppliers" :key="s.id" :value="s.id">
                        {{ s.name }}
                    </option>
                </select>
            </div>
            <div>
                <label class="block text-sm">Almacén</label>
                <select
                    v-model="filters.warehouse_id"
                    class="w-full p-2 border rounded"
                >
                    <option value="">Todos</option>
                    <option v-for="w in warehouses" :key="w.id" :value="w.id">
                        {{ w.name }}
                    </option>
                </select>
            </div>
            <div>
                <label class="block text-sm">Desde</label>
                <input
                    type="date"
                    v-model="filters.date_from"
                    class="w-full p-2 border rounded"
                />
            </div>
            <div>
                <label class="block text-sm">Hasta</label>
                <input
                    type="date"
                    v-model="filters.date_to"
                    class="w-full p-2 border rounded"
                />
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
                <FwbTableHeadCell>ID</FwbTableHeadCell>
                <FwbTableHeadCell>Fecha</FwbTableHeadCell>
                <FwbTableHeadCell>Total (Bs)</FwbTableHeadCell>
                <FwbTableHeadCell>Proveedor</FwbTableHeadCell>
                <FwbTableHeadCell>Almacén</FwbTableHeadCell>
                <FwbTableHeadCell
                    ><span class="sr-only">Acciones</span></FwbTableHeadCell
                >
            </FwbTableHead>
            <FwbTableBody>
                <FwbTableRow v-for="p in purchases.data" :key="p.id">
                    <FwbTableCell>{{ p.id }}</FwbTableCell>
                    <FwbTableCell>{{ p.purchase_date }}</FwbTableCell>
                    <FwbTableCell>{{
                        parseFloat(p.total_amount).toFixed(2)
                    }}</FwbTableCell>
                    <FwbTableCell>{{ nombreProveedor(p) }}</FwbTableCell>
                    <FwbTableCell>{{ nombreAlmacen(p) }}</FwbTableCell>
                    <FwbTableCell class="flex gap-2 justify-end items-center">
                        <button
                            type="button"
                            class="vet-action-btn vet-action-btn--view"
                            title="Ver"
                            @click="viewPurchase(p)"
                        >
                            <i class="fa-solid fa-eye fa-lg"></i>
                        </button>
                        <button
                            type="button"
                            class="vet-action-btn vet-action-btn--view"
                            title="Imprimir"
                            @click="printPurchase(p)"
                        >
                            <i class="fa-solid fa-print fa-lg"></i>
                        </button>
                        <TableActionButtons
                            :can-edit="canEdit"
                            :can-delete="canEdit"
                            @edit="router.get(route('notas-compra.edit', p.id))"
                            @delete="openDeleteModal(p)"
                        />
                    </FwbTableCell>
                </FwbTableRow>
            </FwbTableBody>
        </FwbTable>

        <!-- Mensaje si no hay datos -->
        <div
            v-if="!purchases.data || purchases.data.length === 0"
            class="text-center py-8 text-gray-500"
        >
            <i class="fa-solid fa-inbox text-4xl mb-4"></i>
            <p>No se encontraron notas de compra con los filtros aplicados</p>
        </div>

        <!-- Paginación -->
        <div
            class="flex justify-center my-4"
            v-if="purchases.data && purchases.data.length > 0"
        >
            <FwbPagination
                v-model="currentPage"
                :total-items="purchases.total"
                :per-page="purchases.per_page"
                large
            />
        </div>

        <!-- Delete Modal -->
        <FwbModal v-if="isDeleteModal" @close="isDeleteModal = false">
            <template #header>Confirmar eliminación</template>
            <template #body>
                <p>¿Eliminar nota de compra #{{ deleteTarget.id }}?</p>
            </template>
            <template #footer>
                <FwbButton color="alternative" @click="isDeleteModal = false"
                    >Cancelar</FwbButton
                >
                <FwbButton color="red" @click="submitDelete"
                    >Eliminar</FwbButton
                >
            </template>
        </FwbModal>

        <!-- View Modal -->
        <FwbModal v-if="isShowModal" @close="isShowModal = false">
            <template #header>
                Detalle de Compra #{{ selectedPurchase?.id }}
            </template>
            <template #body>
                <div v-if="selectedPurchase">
                    <p>
                        <strong>Fecha:</strong>
                        {{ selectedPurchase.purchase_date }} &nbsp;&nbsp;
                        <strong>Proveedor:</strong>
                        {{ nombreProveedor(selectedPurchase) }}
                    </p>
                    <div class="overflow-x-auto mt-4">
                        <FwbTable class="min-w-full">
                            <FwbTableHead>
                                <FwbTableHeadCell>Médicamento</FwbTableHeadCell>
                                <FwbTableHeadCell class="text-right"
                                    >Cant.</FwbTableHeadCell
                                >
                                <FwbTableHeadCell class="text-right"
                                    >Precio</FwbTableHeadCell
                                >
                                <FwbTableHeadCell class="text-right"
                                    >Subtotal</FwbTableHeadCell
                                >
                            </FwbTableHead>
                            <FwbTableBody>
                                <FwbTableRow
                                    v-for="d in selectedDetails"
                                    :key="d.id"
                                >
                                    <FwbTableCell>{{
                                        nombreProducto(d)
                                    }}</FwbTableCell>
                                    <FwbTableCell class="text-right">{{
                                        d.quantity
                                    }}</FwbTableCell>
                                    <FwbTableCell class="text-right"
                                        >{{ d.purchase_price }} Bs</FwbTableCell
                                    >
                                    <FwbTableCell class="text-right"
                                        >{{ d.subtotal }} Bs</FwbTableCell
                                    >
                                </FwbTableRow>
                            </FwbTableBody>
                        </FwbTable>
                    </div>
                    <div class="mt-4 text-right">
                        <span class="font-semibold text-lg">
                            Total:
                            {{
                                parseFloat(
                                    selectedPurchase.total_amount
                                ).toFixed(2)
                            }}
                            Bs
                        </span>
                    </div>
                </div>
            </template>
            <template #footer>
                <FwbButton color="alternative" @click="isShowModal = false"
                    >Cerrar</FwbButton
                >
            </template>
        </FwbModal>
    </AdminLayout>
</template>
