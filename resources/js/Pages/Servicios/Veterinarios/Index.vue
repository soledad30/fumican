<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { router } from "@inertiajs/vue3";
import {
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
    FwbToggle,
} from "flowbite-vue";
import { computed, ref, watch } from "vue";
import axios from "axios";
import { usePermisos } from "@/Composables/usePermisos";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import TableActionButtons from "@/Components/TableActionButtons.vue";

const props = defineProps({
    veterinarios: Object,
    especialidades: { type: Array, default: () => [] },
    filters: Object,
});

const filters = ref({ search_term: props.filters?.search_term || "" });
const currentPage = ref(props.veterinarios.current_page || 1);

const loading = ref(false);
const showToast = ref(false);
const toastMsg = ref("");
const toastType = ref("success");

const modalMode = ref("create");
const isCreateOrEditModal = ref(false);
const isViewModal = ref(false);
const isDeleteModal = ref(false);
const selectedVet = ref(null);

const defaultFormState = {
    id: null,
    first_name: "",
    last_name: "",
    ci: "",
    phone_number: "",
    email: "",
    license_number: "",
    is_specialist: false,
    specialty: "",
    esta_activo: true,
};
const form = ref({ ...defaultFormState });
const formErrors = ref({});
const specialtySelect = ref("");
const customSpecialty = ref("");

const { puede } = usePermisos();
const isEmpty = computed(() => !props.veterinarios?.data?.length);
const canCreate = computed(() => puede("crear veterinarios"));
const canEdit = computed(() => puede("editar veterinarios"));
const canDelete = computed(() => puede("eliminar veterinarios"));

function displayToast(type, message) {
    toastType.value = type;
    toastMsg.value = message;
    showToast.value = true;
    setTimeout(() => (showToast.value = false), 3000);
}

function applyFilters() {
    router.get(route("veterinarios.search"), filters.value, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    filters.value = { search_term: "" };
    router.get(route("veterinarios.index"));
}

watch(currentPage, (page) => {
    router.get(
        route("veterinarios.search"),
        { ...filters.value, page },
        { preserveState: true, replace: true }
    );
});

function resetSpecialtyFields() {
    specialtySelect.value = "";
    customSpecialty.value = "";
}

function initSpecialtyFields(value) {
    const trimmed = (value ?? "").trim();
    if (!trimmed) {
        resetSpecialtyFields();
        return;
    }
    if (props.especialidades.includes(trimmed)) {
        specialtySelect.value = trimmed;
        customSpecialty.value = "";
    } else {
        specialtySelect.value = "otro";
        customSpecialty.value = trimmed;
    }
}

function resolveSpecialty() {
    if (!form.value.is_specialist) {
        return null;
    }
    if (specialtySelect.value === "otro") {
        return customSpecialty.value.trim() || null;
    }
    return specialtySelect.value || null;
}

function openCreateModal() {
    modalMode.value = "create";
    form.value = { ...defaultFormState };
    formErrors.value = {};
    resetSpecialtyFields();
    isCreateOrEditModal.value = true;
}

function openEditModal(vet) {
    modalMode.value = "edit";
    selectedVet.value = vet;
    form.value = {
        ...defaultFormState,
        first_name: vet.first_name ?? vet.nombre ?? "",
        last_name: vet.last_name ?? vet.apellido ?? "",
        ci: vet.ci ?? "",
        phone_number: vet.phone_number ?? vet.telefono ?? "",
        email: vet.email ?? "",
        license_number: vet.license_number ?? vet.licencia ?? "",
        is_specialist: Boolean(vet.is_specialist ?? vet.es_especialista),
        specialty: vet.specialty ?? vet.especialidad ?? "",
        esta_activo: vet.esta_activo !== false,
    };
    formErrors.value = {};
    initSpecialtyFields(vet.specialty ?? vet.especialidad ?? "");
    isCreateOrEditModal.value = true;
}

function openViewModal(vet) {
    selectedVet.value = vet;
    isViewModal.value = true;
}

function openDeleteModal(vet) {
    selectedVet.value = vet;
    isDeleteModal.value = true;
}

function closeAllModals() {
    isCreateOrEditModal.value = false;
    isViewModal.value = false;
    isDeleteModal.value = false;
}

watch(
    () => form.value.is_specialist,
    (isSpecialist) => {
        if (!isSpecialist) {
            resetSpecialtyFields();
        }
    }
);

async function submitForm() {
    loading.value = true;
    formErrors.value = {};
    const specialty = resolveSpecialty();
    if (form.value.is_specialist && !specialty) {
        formErrors.value = {
            specialty: [
                specialtySelect.value === "otro"
                    ? "Indique la especialidad personalizada."
                    : "Seleccione una especialidad.",
            ],
        };
        displayToast("danger", "Indique la especialidad del especialista.");
        loading.value = false;
        return;
    }
    const payload = {
        ...form.value,
        is_specialist: Boolean(form.value.is_specialist),
        specialty,
    };
    try {
        if (modalMode.value === "edit") {
            await axios.put(
                route("veterinarios.update", selectedVet.value.id),
                payload
            );
            displayToast("success", "Veterinario actualizado correctamente.");
        } else {
            await axios.post(route("veterinarios.store"), payload);
            displayToast("success", "Veterinario registrado correctamente.");
        }
        closeAllModals();
        router.reload({ only: ["veterinarios", "especialidades"] });
    } catch (e) {
        if (e.response?.data?.errors) {
            formErrors.value = e.response.data.errors;
            const firstError = Object.values(e.response.data.errors)
                .flat()
                .find(Boolean);
            displayToast(
                "danger",
                firstError || "Corrige los errores del formulario."
            );
        } else {
            displayToast(
                "danger",
                e.response?.data?.message || "Ocurrio un error inesperado."
            );
        }
    } finally {
        loading.value = false;
    }
}

async function submitDelete() {
    loading.value = true;
    try {
        await axios.delete(
            route("veterinarios.destroy", selectedVet.value.id)
        );
        displayToast("success", "Veterinario eliminado correctamente.");
        closeAllModals();
        router.reload({ only: ["veterinarios", "especialidades"] });
    } catch {
        displayToast("danger", "Error al eliminar el veterinario.");
    } finally {
        loading.value = false;
    }
}

function tipoLabel(vet) {
    if (vet.is_specialist ?? vet.es_especialista) {
        return vet.specialty ?? vet.especialidad ?? "Especialista";
    }
    return "Veterinario";
}
</script>

<template>
    <AdminLayout title="Veterinarios / Especialistas">
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType" closable>{{
                toastMsg
            }}</FwbToast>
        </div>

        <div class="vet-section-header">
            <div>
                <h2 class="text-2xl font-semibold vet-page-title">
                    Veterinarios / Especialistas
                </h2>
                <p class="vet-page-subtitle mt-1">
                    Personal clínico. Marque «Es especialista» para registrar
                    su especialidad.
                </p>
            </div>
            <FwbButton
                v-if="canCreate"
                @click="openCreateModal"
                color="green"
            >
                <i class="fa-solid fa-plus mr-2"></i>
                Agregar veterinario
            </FwbButton>
        </div>

        <form
            @submit.prevent="applyFilters"
            class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 vet-filter-panel"
        >
            <div class="md:col-span-3">
                <label class="block text-sm font-medium mb-1.5"
                    >Buscar por nombre, correo o especialidad</label
                >
                <TextInput
                    v-model="filters.search_term"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="Ej: Dr. García, Dermatología..."
                />
            </div>
            <div class="flex items-end space-x-2">
                <FwbButton color="green" type="submit">Filtrar</FwbButton>
                <FwbButton color="alternative" @click.prevent="resetFilters"
                    >Limpiar</FwbButton
                >
            </div>
        </form>

        <div class="vet-panel p-0 overflow-hidden">
            <FwbTable class="vet-list-table">
                <FwbTableHead>
                    <FwbTableHeadCell>Nombre</FwbTableHeadCell>
                    <FwbTableHeadCell>Tipo</FwbTableHeadCell>
                    <FwbTableHeadCell>Teléfono</FwbTableHeadCell>
                    <FwbTableHeadCell>Correo</FwbTableHeadCell>
                    <FwbTableHeadCell>Cuenta</FwbTableHeadCell>
                    <FwbTableHeadCell>Estado</FwbTableHeadCell>
                    <FwbTableHeadCell
                        ><span class="sr-only">Acciones</span></FwbTableHeadCell
                    >
                </FwbTableHead>
                <FwbTableBody>
                    <FwbTableRow v-if="isEmpty">
                        <FwbTableCell
                            colspan="7"
                            class="text-center py-8 vet-cell-muted"
                        >
                            No hay veterinarios registrados.
                        </FwbTableCell>
                    </FwbTableRow>
                    <FwbTableRow
                        v-for="vet in veterinarios.data"
                        :key="vet.id"
                    >
                        <FwbTableCell class="vet-cell-primary">{{
                            vet.full_name ||
                            `${vet.first_name ?? vet.nombre ?? ""} ${vet.last_name ?? vet.apellido ?? ""}`.trim()
                        }}</FwbTableCell>
                        <FwbTableCell>
                            <span
                                class="text-xs font-medium px-2 py-1 rounded-full"
                                :class="
                                    vet.is_specialist ?? vet.es_especialista
                                        ? 'bg-violet-100 text-violet-800'
                                        : 'bg-sky-100 text-sky-800'
                                "
                            >
                                {{ tipoLabel(vet) }}
                            </span>
                        </FwbTableCell>
                        <FwbTableCell class="vet-cell-muted">{{
                            vet.phone_number ?? vet.telefono ?? "—"
                        }}</FwbTableCell>
                        <FwbTableCell class="vet-cell-muted">{{
                            vet.email ?? "—"
                        }}</FwbTableCell>
                        <FwbTableCell class="vet-cell-muted">
                            {{
                                vet.usuario_id || vet.usuario
                                    ? "Vinculada"
                                    : "Sin cuenta"
                            }}
                        </FwbTableCell>
                        <FwbTableCell>
                            <span
                                class="text-xs font-medium px-2 py-1 rounded-full"
                                :class="
                                    vet.esta_activo !== false
                                        ? 'bg-emerald-100 text-emerald-800'
                                        : 'bg-gray-200 text-gray-600'
                                "
                            >
                                {{
                                    vet.esta_activo !== false
                                        ? "Activo"
                                        : "Baja"
                                }}
                            </span>
                        </FwbTableCell>
                        <FwbTableCell class="space-x-3 whitespace-nowrap">
                            <TableActionButtons
                                :can-view="true"
                                :can-edit="canEdit"
                                :can-delete="canDelete"
                                view-title="Ver"
                                @view="openViewModal(vet)"
                                @edit="openEditModal(vet)"
                                @delete="openDeleteModal(vet)"
                            />
                        </FwbTableCell>
                    </FwbTableRow>
                </FwbTableBody>
            </FwbTable>
        </div>

        <div v-if="!isEmpty" class="flex justify-center my-4">
            <FwbPagination
                v-model="currentPage"
                :total-items="veterinarios.total"
                :per-page="veterinarios.per_page"
                large
            />
        </div>

        <FwbModal size="lg" v-if="isViewModal" @close="closeAllModals">
            <template #header
                ><h3 class="text-xl font-semibold">Detalle</h3></template
            >
            <template #body>
                <div v-if="selectedVet" class="space-y-2 text-sm">
                    <p>
                        <strong>Nombre:</strong>
                        {{
                            selectedVet.full_name ||
                            `${selectedVet.first_name ?? selectedVet.nombre} ${selectedVet.last_name ?? selectedVet.apellido ?? ""}`
                        }}
                    </p>
                    <p><strong>CI:</strong> {{ selectedVet.ci || "—" }}</p>
                    <p>
                        <strong>Teléfono:</strong>
                        {{
                            selectedVet.phone_number ??
                            selectedVet.telefono ??
                            "—"
                        }}
                    </p>
                    <p>
                        <strong>Correo:</strong> {{ selectedVet.email || "—" }}
                    </p>
                    <p>
                        <strong>Licencia:</strong>
                        {{
                            selectedVet.license_number ??
                            selectedVet.licencia ??
                            "—"
                        }}
                    </p>
                    <p>
                        <strong>Tipo:</strong>
                        {{
                            selectedVet.is_specialist ??
                            selectedVet.es_especialista
                                ? `Especialista — ${selectedVet.specialty ?? selectedVet.especialidad}`
                                : "Veterinario general"
                        }}
                    </p>
                </div>
            </template>
            <template #footer>
                <div class="flex justify-end w-full">
                    <FwbButton @click="closeAllModals" color="alternative"
                        >Cerrar</FwbButton
                    >
                </div>
            </template>
        </FwbModal>

        <FwbModal v-if="isDeleteModal" @close="closeAllModals">
            <template #header>Confirmar eliminación</template>
            <template #body>
                <p class="text-center">
                    ¿Eliminar a
                    <strong>{{ selectedVet?.full_name ?? selectedVet?.nombre }}</strong
                    >?
                </p>
            </template>
            <template #footer>
                <div class="flex justify-center w-full">
                    <FwbButton @click="closeAllModals" color="alternative"
                        >Cancelar</FwbButton
                    >
                    <FwbButton
                        @click="submitDelete"
                        color="red"
                        :loading="loading"
                        class="ml-2"
                        >Eliminar</FwbButton
                    >
                </div>
            </template>
        </FwbModal>

        <FwbModal
            size="2xl"
            v-if="isCreateOrEditModal"
            @close="closeAllModals"
        >
            <template #header>
                <h3 class="text-xl font-semibold">
                    {{
                        modalMode === "edit"
                            ? "Editar veterinario"
                            : "Registrar veterinario"
                    }}
                </h3>
            </template>
            <template #body>
                <form class="space-y-4" @submit.prevent="submitForm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Nombre" />
                            <TextInput
                                v-model="form.first_name"
                                class="mt-1 w-full"
                            />
                            <InputError
                                :message="formErrors.first_name?.[0]"
                            />
                        </div>
                        <div>
                            <InputLabel value="Apellido" />
                            <TextInput
                                v-model="form.last_name"
                                class="mt-1 w-full"
                            />
                            <InputError
                                :message="formErrors.last_name?.[0]"
                            />
                        </div>
                        <div>
                            <InputLabel value="CI" />
                            <TextInput v-model="form.ci" class="mt-1 w-full" />
                            <InputError :message="formErrors.ci?.[0]" />
                        </div>
                        <div>
                            <InputLabel value="Teléfono" />
                            <TextInput
                                v-model="form.phone_number"
                                class="mt-1 w-full"
                            />
                            <InputError
                                :message="formErrors.phone_number?.[0]"
                            />
                        </div>
                        <div>
                            <InputLabel value="Correo" />
                            <TextInput
                                v-model="form.email"
                                type="email"
                                class="mt-1 w-full"
                                placeholder="nombre@clinica.com"
                            />
                            <InputError :message="formErrors.email?.[0]" />
                        </div>
                        <div>
                            <InputLabel value="Nº licencia" />
                            <TextInput
                                v-model="form.license_number"
                                class="mt-1 w-full"
                            />
                            <InputError
                                :message="formErrors.license_number?.[0]"
                            />
                        </div>
                        <div class="md:col-span-2 flex items-center gap-3">
                            <FwbToggle v-model="form.is_specialist" label="Es especialista" />
                        </div>
                        <div v-if="form.is_specialist" class="md:col-span-2">
                            <InputLabel value="Especialidad" />
                            <select
                                v-model="specialtySelect"
                                class="vet-select w-full mt-1"
                            >
                                <option value="" disabled>
                                    Seleccione una especialidad
                                </option>
                                <option
                                    v-for="esp in especialidades"
                                    :key="esp"
                                    :value="esp"
                                >
                                    {{ esp }}
                                </option>
                                <option value="otro">Otra...</option>
                            </select>
                            <TextInput
                                v-if="specialtySelect === 'otro'"
                                v-model="customSpecialty"
                                type="text"
                                class="mt-2 w-full"
                                placeholder="Escriba la especialidad..."
                            />
                            <InputError
                                :message="formErrors.specialty?.[0]"
                            />
                        </div>
                        <div
                            v-if="modalMode === 'edit'"
                            class="md:col-span-2 flex items-center gap-3"
                        >
                            <FwbToggle
                                v-model="form.esta_activo"
                                label="Activo"
                            />
                        </div>
                    </div>
                </form>
            </template>
            <template #footer>
                <div class="flex justify-end">
                    <FwbButton @click="closeAllModals" color="alternative"
                        >Cancelar</FwbButton
                    >
                    <FwbButton
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

<style scoped>
.vet-select {
    display: block;
    border-radius: 0.5rem;
    border: 1px solid var(--color-border, #d1d5db);
    background-color: var(--color-surface, #fff);
    color: var(--color-text, #111827);
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
}

.vet-select:focus {
    outline: none;
    border-color: var(--color-primary, #f97316);
    box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-primary, #f97316) 25%, transparent);
}
</style>
