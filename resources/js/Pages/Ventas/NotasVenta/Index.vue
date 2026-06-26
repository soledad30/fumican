<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref, watch, computed } from "vue";
import axios from "axios";
import { router } from "@inertiajs/vue3";
import { usePermisos } from "@/Composables/usePermisos";
import {
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
} from "flowbite-vue";
import TableActionButtons from "@/Components/TableActionButtons.vue";

// Props desde el backend
const props = defineProps({
    sales: Object,
    customers: Array,
    warehouses: Array,
    filters: Object,
});

// Estado reactivo para paginación y filtros
const currentPage = ref(props.sales.current_page);
const filters = ref({
    customer_id: props.filters?.customer_id || "",
    warehouse_id: props.filters?.warehouse_id || "",
    date_from: props.filters?.date_from || "",
    date_to: props.filters?.date_to || "",
    per_page: props.filters?.per_page || 15,
});

// Toast y estados de modal
const showToast = ref(false);
const { puede } = usePermisos();
const canCreate = computed(() => puede("crear notas de ventas"));
const canEdit = computed(() => puede("editar notas de ventas"));
const toastMsg = ref("");
const toastType = ref("success");

const isDeleteModal = ref(false);
const deleteTarget = ref(null);

const isShowModal = ref(false);
const selectedSale = ref(null);
const selectedDetails = ref([]);

function nombreCliente(registro) {
    const c = registro?.cliente ?? registro?.customer;
    if (!c) return "—";
    const nombre = c.first_name ?? c.nombre ?? "";
    const apellido = c.last_name ?? c.apellido ?? "";
    return `${nombre} ${apellido}`.trim() || "—";
}

function nombreAlmacen(registro) {
    const a = registro?.almacen ?? registro?.warehouse;
    return a?.name ?? a?.nombre ?? "—";
}

function nombreUsuario(registro) {
    const u = registro?.usuario ?? registro?.user;
    if (!u) return "—";
    const nombre = u.first_name ?? u.nombre ?? "";
    const apellido = u.last_name ?? "";
    return `${nombre} ${apellido}`.trim() || u.full_name || "—";
}

function nombreProducto(detalle) {
    const p = detalle?.producto ?? detalle?.medicament;
    return p?.name ?? p?.nombre ?? "—";
}

// Watch para paginación
watch(currentPage, (page) => {
    router.get(
        route("notas-venta.search"),
        { ...filters.value, page },
        { preserveState: true, replace: true }
    );
});

// Acciones
function openDeleteModal(sale) {
    deleteTarget.value = sale;
    isDeleteModal.value = true;
}

async function submitDelete() {
    try {
        await axios.delete(route("notas-venta.destroy", deleteTarget.value.id));
        toastType.value = "success";
        toastMsg.value = "Venta eliminada correctamente";
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
    router.get(route("notas-venta.search"), filters.value, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    Object.assign(filters.value, {
        customer_id: "",
        warehouse_id: "",
        date_from: "",
        date_to: "",
        per_page: 15,
    });
    router.get(
        route("notas-venta.index"),
        {},
        {
            preserveState: true,
            replace: true,
        }
    );
}

// Modal de detalles
async function viewSale(sale) {
    try {
        const { data } = await axios.get(route("notas-venta.show", sale.id), {
            headers: { Accept: "application/json" },
        });
        selectedSale.value = data.salesNote;
        selectedDetails.value = data.salesNoteDetails;
        isShowModal.value = true;
    } catch {
        toastType.value = "danger";
        toastMsg.value = "Error cargando detalle";
        showToast.value = true;
        setTimeout(() => (showToast.value = false), 2500);
    }
}

// Imprimir PDF individual
function printSale(sale) {
    window.open(route("notas-venta.pdf", { id: sale.id }), "_blank");
}
</script>

<template>
    <AdminLayout title="Notas de Venta">
        <!-- Toast -->
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType">{{
                toastMsg
            }}</FwbToast>
        </div>

        <div class="flex justify-between items-center my-6">
            <h2 class="text-2xl font-semibold vet-page-title">Notas de Venta</h2>
            <div class="space-x-2">
                <FwbButton
                    v-if="canCreate"
                    color="green"
                    @click="router.get(route('notas-venta.create'))"
                >
                    <i class="fa-solid fa-plus mr-2"></i> Nueva Venta
                </FwbButton>
                <FwbButton
                    tag="a"
                    :href="
                        route('notas-venta.report', {
                            customer_id: filters.customer_id,
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
                <label class="block text-sm">Cliente</label>
                <select
                    v-model="filters.customer_id"
                    class="w-full p-2 border rounded"
                >
                    <option value="">Todos</option>
                    <option v-for="c in customers" :key="c.id" :value="c.id">
                        {{ c.first_name }} {{ c.last_name }}
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
                <FwbTableHeadCell>Cliente</FwbTableHeadCell>
                <FwbTableHeadCell>Almacén</FwbTableHeadCell>
                <FwbTableHeadCell
                    ><span class="sr-only">Acciones</span></FwbTableHeadCell
                >
            </FwbTableHead>
            <FwbTableBody>
                <FwbTableRow v-for="s in sales.data" :key="s.id">
                    <FwbTableCell>{{ s.id }}</FwbTableCell>
                    <FwbTableCell>{{ s.sale_date }}</FwbTableCell>
                    <FwbTableCell>{{
                        parseFloat(s.total_amount).toFixed(2)
                    }}</FwbTableCell>
                    <FwbTableCell>{{ nombreCliente(s) }}</FwbTableCell>
                    <FwbTableCell>{{ nombreAlmacen(s) }}</FwbTableCell>
                    <FwbTableCell class="flex gap-2 justify-end items-center">
                        <button
                            type="button"
                            class="vet-action-btn vet-action-btn--view"
                            title="Ver"
                            @click="viewSale(s)"
                        >
                            <i class="fa-solid fa-eye fa-lg"></i>
                        </button>
                        <button
                            type="button"
                            class="vet-action-btn vet-action-btn--view"
                            title="Imprimir"
                            @click="printSale(s)"
                        >
                            <i class="fa-solid fa-print fa-lg"></i>
                        </button>
                        <TableActionButtons
                            :can-edit="canEdit"
                            :can-delete="canEdit"
                            @edit="router.get(route('notas-venta.edit', s.id))"
                            @delete="openDeleteModal(s)"
                        />
                    </FwbTableCell>
                </FwbTableRow>
            </FwbTableBody>
        </FwbTable>

        <!-- Mensaje si no hay datos -->
        <div
            v-if="!sales.data || sales.data.length === 0"
            class="text-center py-8 text-gray-500"
        >
            <i class="fa-solid fa-inbox text-4xl mb-4"></i>
            <p>No se encontraron ventas con los filtros aplicados</p>
        </div>

        <!-- Paginación -->
        <div
            class="flex justify-center my-4"
            v-if="sales.data && sales.data.length > 0"
        >
            <FwbPagination
                v-model="currentPage"
                :total-items="sales.total"
                :per-page="sales.per_page"
                large
            />
        </div>

        <!-- Delete Modal -->
        <FwbModal v-if="isDeleteModal" @close="isDeleteModal = false">
            <template #header>Confirmar eliminación</template>
            <template #body>
                <p>¿Eliminar nota de venta #{{ deleteTarget.id }}?</p>
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
                Detalle de Venta #{{ selectedSale?.id }}
            </template>
            <template #body>
                <div v-if="selectedSale">
                    <p>
                        <strong>Fecha:</strong>
                        {{ selectedSale.sale_date }} &nbsp;&nbsp;
                        <strong>Cliente:</strong>
                        {{ nombreCliente(selectedSale) }}
                    </p>
                    <p>
                        <strong>Usuario:</strong>
                        {{ nombreUsuario(selectedSale) }}
                    </p>
                    <div class="overflow-x-auto mt-4">
                        <FwbTable class="min-w-full">
                            <FwbTableHead>
                                <FwbTableHeadCell>Medicamento</FwbTableHeadCell>
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
                                        >{{ d.sale_price }} Bs</FwbTableCell
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
                                parseFloat(selectedSale.total_amount).toFixed(2)
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
