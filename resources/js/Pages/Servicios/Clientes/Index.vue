<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { usePage, router } from "@inertiajs/vue3";
import {
    FwbA,
    FwbButton,
    FwbPagination,
    FwbTable,
    FwbTableBody,
    FwbTableCell,
    FwbTableHead,
    FwbTableHeadCell,
    FwbTableRow,
    FwbModal,
    FwbToast,
    FwbRadio,
} from "flowbite-vue";
import { computed, ref, watch } from "vue";
import axios from "axios";
import { usePermisos } from "@/Composables/usePermisos";

// Components
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import TableActionButtons from "@/Components/TableActionButtons.vue";

// --- PROPS & FILTERS ---
const props = defineProps({ customers: Object, filters: Object });

const filters = ref({ search_term: props.filters.search_term || "" });
function applyFilters() {
    router.get(route("clientes.search"), filters.value, {
        preserveState: true,
        replace: true,
    });
}
function resetFilters() {
    filters.value = { search_term: "" };
    router.get(route("clientes.index"));
}

// --- PAGINATION ---
const currentPage = ref(props.customers.current_page || 1);
watch(currentPage, (newPage) => {
    router.get(
        route("clientes.search"),
        { ...filters.value, page: newPage },
        { preserveState: true, replace: true }
    );
});

// --- STATE MANAGEMENT ---
const loading = ref(false);
const showToast = ref(false);
const toastMsg = ref("");
const toastType = ref("success");

// --- MODALS ---
const modalMode = ref("create");
const isCreateOrEditModal = ref(false);
const isViewModal = ref(false);
const isDeleteModal = ref(false);
const selectedCustomer = ref(null);

// --- FORM ---
const defaultFormState = {
    id: null,
    first_name: "",
    last_name: "",
    ci: "",
    phone_number: "",
    gender: "0",
    birthdate: "",
    address: "",
};
const form = ref({ ...defaultFormState });
const formErrors = ref({});

// --- HELPERS & PERMISSIONS ---
const { puede } = usePermisos();
const isEmptyData = computed(() => props.customers.data.length === 0);
const page = usePage();
const canCreateCustomers = computed(() => puede("crear clientes"));
const canEditCustomers = computed(() => puede("editar clientes"));
const canDeleteCustomers = computed(() => puede("eliminar clientes"));

// --- FUNCTIONS ---
function displayToast(type, message) {
    toastType.value = type;
    toastMsg.value = message;
    showToast.value = true;
    setTimeout(() => (showToast.value = false), 3000);
}

function openCreateModal() {
    modalMode.value = "create";
    form.value = { ...defaultFormState };
    formErrors.value = {};
    isCreateOrEditModal.value = true;
}

function openEditModal(customer) {
    modalMode.value = "edit";
    selectedCustomer.value = customer;
    form.value = {
        ...defaultFormState,
        ...customer,
        gender: String(customer.gender ?? "0"),
    };
    formErrors.value = {};
    isCreateOrEditModal.value = true;
}

function openViewModal(customer) {
    selectedCustomer.value = customer;
    isViewModal.value = true;
}

function openDeleteModal(customer) {
    selectedCustomer.value = customer;
    isDeleteModal.value = true;
}

function closeAllModals() {
    isCreateOrEditModal.value = false;
    isViewModal.value = false;
    isDeleteModal.value = false;
}

// --- CRUD ---
async function submitForm() {
    loading.value = true;
    formErrors.value = {};
    try {
        if (modalMode.value === "edit") {
            await axios.put(
                route("clientes.update", selectedCustomer.value.id),
                form.value
            );
            displayToast("success", "Cliente actualizado correctamente.");
        } else {
            await axios.post(route("clientes.store"), form.value);
            displayToast("success", "Cliente registrado correctamente.");
        }
        closeAllModals();
        router.reload({ only: ["customers"] });
    } catch (e) {
        if (e.response?.status === 422) {
            formErrors.value = e.response.data.errors;
            displayToast(
                "danger",
                "Por favor, corrige los errores del formulario."
            );
        } else {
            displayToast("danger", "Ocurrió un error inesperado.");
        }
    } finally {
        loading.value = false;
    }
}

async function submitDelete() {
    loading.value = true;
    try {
        await axios.delete(
            route("clientes.destroy", selectedCustomer.value.id)
        );
        displayToast("success", "Cliente eliminado correctamente.");
        closeAllModals();
        router.reload({ only: ["customers"] });
    } catch (e) {
        displayToast("danger", "Error al eliminar el cliente.");
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <AdminLayout title="Clientes">
        <!-- Toast Notification -->
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType" closable>{{
                toastMsg
            }}</FwbToast>
        </div>

        <!-- Header & Actions -->
        <div class="flex justify-between my-6 items-center">
            <h2 class="text-2xl font-semibold vet-page-title">Clientes</h2>
            <FwbButton
                v-if="canCreateCustomers"
                @click="openCreateModal"
                color="green"
            >
                <i class="fa-solid fa-plus mr-2"></i>Agregar Cliente
            </FwbButton>
        </div>

        <!-- Filters -->
        <form
            @submit.prevent="applyFilters"
            class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 vet-filter-panel"
        >
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700"
                    >Buscar por Nombre, Apellido o CI</label
                >
                <TextInput
                    v-model="filters.search_term"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="Escriba para buscar..."
                />
            </div>
            <div class="flex items-end space-x-2">
                <FwbButton color="green" type="submit">Filtrar</FwbButton>
                <FwbButton color="alternative" @click.prevent="resetFilters"
                    >Limpiar</FwbButton
                >
            </div>
        </form>

        <!-- Customers Table -->
        <FwbTable>
            <FwbTableHead>
                <FwbTableHeadCell>Nombre</FwbTableHeadCell>
                <FwbTableHeadCell>Apellido</FwbTableHeadCell>
                <FwbTableHeadCell>CI</FwbTableHeadCell>
                <FwbTableHeadCell>Teléfono</FwbTableHeadCell>
                <FwbTableHeadCell>Dirección</FwbTableHeadCell>
                <FwbTableHeadCell>Actualizado</FwbTableHeadCell>
                <FwbTableHeadCell
                    ><span class="sr-only">Acciones</span></FwbTableHeadCell
                >
            </FwbTableHead>
            <FwbTableBody>
                <FwbTableRow v-if="isEmptyData">
                    <FwbTableCell
                        colspan="7"
                        class="text-center py-4 text-gray-500"
                        >No se encontraron clientes.</FwbTableCell
                    >
                </FwbTableRow>
                <FwbTableRow
                    v-for="customer in customers.data"
                    :key="customer.id"
                >
                    <FwbTableCell>{{ customer.first_name }}</FwbTableCell>
                    <FwbTableCell>{{ customer.last_name }}</FwbTableCell>
                    <FwbTableCell>{{ customer.ci }}</FwbTableCell>
                    <FwbTableCell>{{ customer.phone_number }}</FwbTableCell>
                    <FwbTableCell class="max-w-xs truncate" :title="customer.address || ''">
                        {{ customer.address || "—" }}
                    </FwbTableCell>
                    <FwbTableCell>{{ customer.updated_at }}</FwbTableCell>
                    <FwbTableCell class="space-x-4 whitespace-nowrap">
                        <TableActionButtons
                            :can-view="true"
                            :can-edit="canEditCustomers"
                            :can-delete="canDeleteCustomers"
                            view-title="Ver detalles"
                            @view="openViewModal(customer)"
                            @edit="openEditModal(customer)"
                            @delete="openDeleteModal(customer)"
                        />
                    </FwbTableCell>
                </FwbTableRow>
            </FwbTableBody>
        </FwbTable>
        <div v-if="!isEmptyData" class="flex justify-center my-4">
            <FwbPagination
                v-model="currentPage"
                :total-items="customers.total"
                :per-page="customers.per_page"
                large
            />
        </div>

        <!-- View Modal -->
        <FwbModal size="lg" v-if="isViewModal" @close="closeAllModals">
            <template #header
                ><h3 class="text-xl font-semibold">
                    Detalles del Cliente
                </h3></template
            >
            <template #body>
                <div v-if="selectedCustomer" class="space-y-4 text-sm">
                    <p>
                        <strong>Nombre:</strong>
                        {{ selectedCustomer.first_name }}
                    </p>
                    <p>
                        <strong>Apellido:</strong>
                        {{ selectedCustomer.last_name }}
                    </p>
                    <p><strong>CI:</strong> {{ selectedCustomer.ci }}</p>
                    <p>
                        <strong>Teléfono:</strong>
                        {{ selectedCustomer.phone_number }}
                    </p>
                    <p>
                        <strong>Género:</strong>
                        {{
                            { 0: "Masculino", 1: "Femenino", 2: "Otro" }[
                                selectedCustomer.gender
                            ]
                        }}
                    </p>
                    <p>
                        <strong>Fecha de Nacimiento:</strong>
                        {{ selectedCustomer.birthdate || "—" }}
                    </p>
                    <p>
                        <strong>Dirección:</strong>
                        {{ selectedCustomer.address || "—" }}
                    </p>
                </div>
            </template>
            <template #footer
                ><div class="flex justify-end w-full">
                    <FwbButton @click="closeAllModals" color="alternative"
                        >Cerrar</FwbButton
                    >
                </div></template
            >
        </FwbModal>

        <!-- Delete Modal -->
        <FwbModal v-if="isDeleteModal" @close="closeAllModals">
            <template #header>Confirmar Eliminación</template>
            <template #body
                ><p class="text-center text-lg">
                    ¿Seguro que deseas eliminar a
                    <strong
                        >{{ selectedCustomer?.first_name }}
                        {{ selectedCustomer?.last_name }}</strong
                    >?
                </p></template
            >
            <template #footer>
                <div class="flex justify-center w-full">
                    <FwbButton @click="closeAllModals" color="alternative"
                        >Cancelar</FwbButton
                    ><FwbButton
                        @click="submitDelete"
                        color="red"
                        :loading="loading"
                        class="ml-2"
                        >Eliminar</FwbButton
                    >
                </div>
            </template>
        </FwbModal>

        <!-- Create/Edit Modal -->
        <FwbModal size="2xl" v-if="isCreateOrEditModal" @close="closeAllModals">
            <template #header
                ><h3 class="text-xl font-semibold">
                    {{ modalMode === "edit" ? "Editar" : "Registrar" }} Cliente
                </h3></template
            >
            <template #body>
                <form class="space-y-4" @submit.prevent="submitForm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Nombre" /><TextInput
                                v-model="form.first_name"
                                class="mt-1 w-full"
                            /><InputError
                                :message="formErrors.first_name?.[0]"
                            />
                        </div>
                        <div>
                            <InputLabel value="Apellido" /><TextInput
                                v-model="form.last_name"
                                class="mt-1 w-full"
                            /><InputError
                                :message="formErrors.last_name?.[0]"
                            />
                        </div>
                        <div>
                            <InputLabel value="CI" /><TextInput
                                v-model="form.ci"
                                class="mt-1 w-full"
                            /><InputError :message="formErrors.ci?.[0]" />
                        </div>
                        <div>
                            <InputLabel value="Teléfono" /><TextInput
                                v-model="form.phone_number"
                                class="mt-1 w-full"
                            /><InputError
                                :message="formErrors.phone_number?.[0]"
                            />
                        </div>
                        <div>
                            <InputLabel value="Género" class="mb-2" />
                            <div class="flex gap-4">
                                <FwbRadio
                                    v-model="form.gender"
                                    value="0"
                                    label="Masculino"
                                /><FwbRadio
                                    v-model="form.gender"
                                    value="1"
                                    label="Femenino"
                                /><FwbRadio
                                    v-model="form.gender"
                                    value="2"
                                    label="Otro"
                                />
                            </div>
                            <InputError :message="formErrors.gender?.[0]" />
                        </div>
                        <div>
                            <InputLabel value="Fecha de Nacimiento" /><TextInput
                                v-model="form.birthdate"
                                type="date"
                                class="mt-1 w-full"
                            /><InputError
                                :message="formErrors.birthdate?.[0]"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <InputLabel value="Dirección" /><TextInput
                                v-model="form.address"
                                type="text"
                                class="mt-1 w-full"
                                placeholder="Calle, zona, ciudad..."
                            /><InputError
                                :message="formErrors.address?.[0]"
                            />
                        </div>
                    </div>
                </form>
            </template>
            <template #footer>
                <div class="flex justify-end">
                    <FwbButton @click="closeAllModals" color="alternative"
                        >Cancelar</FwbButton
                    ><FwbButton
                        @click="submitForm"
                        color="green"
                        :loading="loading"
                        class="ml-2"
                        >Guardar</FwbButton
                    >
                </div>
            </template>
        </FwbModal>
    </AdminLayout>
</template>
