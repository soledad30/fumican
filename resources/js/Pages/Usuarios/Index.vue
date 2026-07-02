<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { usePage, router } from "@inertiajs/vue3";
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
import { computed, ref, watch, inject } from "vue";
import axios from "axios";
import { usePermisos } from "@/Composables/usePermisos";
import { useFormErrors } from "@/Composables/useFormErrors";

const route = inject("route");

// Components
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import TableActionButtons from "@/Components/TableActionButtons.vue";

// --- PROPS & FILTERS ---
const props = defineProps({
    users: Object,
    roles: Array,
    filters: Object,
    vinculosDisponibles: Object,
});

const filters = ref({
    search_term: props.filters?.search_term || "",
    role_id: props.filters?.role_id || "",
});

/** Siempre lista plana de roles (evita objeto paginado cuando la URL lleva ?page=). */
const rolesLista = computed(() => {
    if (Array.isArray(props.roles)) {
        return props.roles.filter(Boolean);
    }
    if (props.roles?.data && Array.isArray(props.roles.data)) {
        return props.roles.data.filter(Boolean);
    }
    return [];
});

function applyFilters() {
    router.get(route("usuarios.search"), filters.value, {
        preserveState: true,
        replace: true,
    });
}
function resetFilters() {
    filters.value = { search_term: "", role_id: "" };
    router.get(route("usuarios.index"));
}

// --- PAGINATION ---
const currentPage = ref(props.users?.current_page || 1);
watch(
    () => props.users?.current_page,
    (page) => {
        if (page && page !== currentPage.value) {
            currentPage.value = page;
        }
    }
);
watch(currentPage, (newPage) => {
    router.get(
        route("usuarios.search"),
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
const selectedUser = ref(null);
const editingUserId = ref(null);

// --- FORM ---
const defaultFormState = {
    id: null,
    first_name: "",
    last_name: "",
    email: "",
    role_id: "",
    esta_activo: true,
    password: "",
    password_confirmation: "",
    cliente_id: "",
    veterinario_id: "",
    reactivar_usuario_id: "",
    vinculo_id: "",
    ci: "",
    telefono: "",
    fecha_nacimiento: "",
    direccion: "",
    especialidad: "",
};
const form = ref({ ...defaultFormState });
const { clearErrors, fromAxios, get } = useFormErrors();
const autoPassword = ref(true);
const showPassword = ref(false);

// --- HELPERS & PERMISSIONS ---
const page = usePage();
const { puede, esAdmin } = usePermisos();
const canCreateUsers = computed(() => esAdmin.value || puede("crear usuarios"));
const canEditUsers = computed(() => esAdmin.value || puede("editar usuarios"));
const canDeleteUsers = computed(() => esAdmin.value || puede("administrar_sistema"));

const rolesDisponibles = computed(() => {
    const lista = rolesLista.value;
    if (esAdmin.value) {
        return lista;
    }
    return lista.filter((r) => {
        const nombre = (r.name || r.nombre || "").toLowerCase();
        return !["propietario", "administrador"].includes(nombre);
    });
});

const rolSeleccionado = computed(() =>
    rolesLista.value.find((r) => String(r.id) === String(form.value.role_id))
);

const nombreRolSeleccionado = computed(
    () => (rolSeleccionado.value?.name || rolSeleccionado.value?.nombre || "").toLowerCase()
);

const requiereVinculo = computed(() =>
    ["cliente", "veterinario"].includes(nombreRolSeleccionado.value)
);

const esRolSistema = computed(() =>
    ["propietario", "administrador"].includes(nombreRolSeleccionado.value)
);

const rolClienteId = computed(
    () => rolesLista.value.find((r) => (r.name || r.nombre) === "cliente")?.id
);
const rolVeterinarioId = computed(
    () => rolesLista.value.find((r) => (r.name || r.nombre) === "veterinario")?.id
);

const listaVinculos = computed(() => {
    if (String(form.value.role_id) === String(rolClienteId.value)) {
        return (props.vinculosDisponibles?.clientes ?? []).map((c) => ({
            id: `cliente-${c.id}`,
            tipo: "cliente",
            label: `${c.nombre} ${c.apellido ?? ""}`.trim() + (c.ci ? ` (CI: ${c.ci})` : ""),
            item: c,
        }));
    }
    if (String(form.value.role_id) === String(rolVeterinarioId.value)) {
        return (props.vinculosDisponibles?.veterinarios ?? []).map((v) => ({
            id: `vet-${v.id}`,
            tipo: "veterinario",
            label:
                `${v.nombre} ${v.apellido ?? ""}`.trim() +
                (v.es_especialista && v.especialidad
                    ? ` — ${v.especialidad}`
                    : "") +
                (v.email ? ` (${v.email})` : ""),
            item: v,
        }));
    }
    return [];
});

const etiquetaVinculos = computed(() => {
    if (String(form.value.role_id) === String(rolClienteId.value)) {
        return "Vincular con cliente existente";
    }
    if (String(form.value.role_id) === String(rolVeterinarioId.value)) {
        return "Vincular con veterinario existente";
    }
    return "";
});

watch(
    () => form.value.role_id,
    () => {
        form.value.vinculo_id = "";
        form.value.cliente_id = "";
        form.value.veterinario_id = "";
        form.value.reactivar_usuario_id = "";
    }
);

function onVinculoChange() {
    const sel = listaVinculos.value.find((v) => v.id === form.value.vinculo_id);
    if (!sel) {
        form.value.cliente_id = "";
        form.value.veterinario_id = "";
        form.value.reactivar_usuario_id = "";
        return;
    }
    if (sel.tipo === "cliente") {
        form.value.cliente_id = sel.item.id;
        form.value.reactivar_usuario_id = "";
        form.value.first_name = sel.item.nombre ?? "";
        form.value.last_name = sel.item.apellido ?? "";
    } else {
        form.value.veterinario_id = sel.item.id;
        form.value.reactivar_usuario_id = "";
        form.value.cliente_id = "";
        form.value.first_name = sel.item.nombre ?? "";
        form.value.last_name = sel.item.apellido ?? "";
        form.value.email = sel.item.email ?? "";
    }
}

// --- FUNCTIONS ---
function displayToast(type, message) {
    toastType.value = type;
    toastMsg.value = message;
    showToast.value = true;
    setTimeout(() => (showToast.value = false), 3000);
}

function openCreateModal() {
    modalMode.value = "create";
    editingUserId.value = null;
    autoPassword.value = true;
    form.value = { ...defaultFormState };
    clearErrors();
    isCreateOrEditModal.value = true;
}

function openEditModal(user) {
    modalMode.value = "edit";
    selectedUser.value = user;
    editingUserId.value = user?.id ?? null;
    autoPassword.value = true;
    form.value = {
        ...defaultFormState,
        id: user.id,
        first_name: user.first_name ?? "",
        last_name: user.last_name ?? "",
        email: user.email ?? "",
        role_id: user.role?.id ?? user.rol_id ?? "",
        esta_activo: user.esta_activo ?? true,
        password: "",
        password_confirmation: "",
        ci: user.ci ?? user.cliente_perfil?.ci ?? "",
        telefono: user.telefono ?? "",
        fecha_nacimiento: user.fecha_nacimiento ? String(user.fecha_nacimiento).slice(0, 10) : "",
        direccion: user.direccion ?? "",
        especialidad: user.especialidad ?? "",
    };
    clearErrors();
    isCreateOrEditModal.value = true;
}

function openViewModal(user) {
    selectedUser.value = user;
    isViewModal.value = true;
}

function openDeleteModal(user) {
    selectedUser.value = user;
    editingUserId.value = user?.id ?? null;
    form.value.id = user.id;
    isDeleteModal.value = true;
}

function closeAllModals() {
    isCreateOrEditModal.value = false;
    isViewModal.value = false;
    isDeleteModal.value = false;
}

// --- CRUD ---
function resolveUsuarioId() {
    const id =
        editingUserId.value ??
        form.value.id ??
        selectedUser.value?.id;
    return id != null && id !== "" ? id : null;
}

function usuarioUpdateUrl(id) {
    return route("usuarios.update", { id });
}

function usuarioDestroyUrl(id) {
    return route("usuarios.destroy", { id });
}

function buildUsuarioPayload() {
    const payload = {
        first_name: form.value.first_name?.trim() ?? "",
        last_name: form.value.last_name?.trim() ?? "",
        email: form.value.email?.trim() ?? "",
        role_id: form.value.role_id,
    };

    if (modalMode.value === "edit") {
        payload.esta_activo = form.value.esta_activo;
    }

    if (!autoPassword.value) {
        payload.password = form.value.password;
        payload.password_confirmation = form.value.password_confirmation;
    }

    if (form.value.ci) payload.ci = form.value.ci;
    if (form.value.telefono) payload.telefono = form.value.telefono;
    if (form.value.fecha_nacimiento) payload.fecha_nacimiento = form.value.fecha_nacimiento;
    if (form.value.direccion) payload.direccion = form.value.direccion;
    if (form.value.especialidad) payload.especialidad = form.value.especialidad;

    if (modalMode.value === "create") {
        if (form.value.cliente_id) {
            payload.cliente_id = form.value.cliente_id;
        }
        if (form.value.veterinario_id) {
            payload.veterinario_id = form.value.veterinario_id;
        }
        if (form.value.reactivar_usuario_id) {
            payload.reactivar_usuario_id = form.value.reactivar_usuario_id;
        }
    }

    return payload;
}

async function submitForm() {
    loading.value = true;
    clearErrors();

    const payload = buildUsuarioPayload();

    try {
        if (modalMode.value === "edit") {
            const id = resolveUsuarioId();
            if (!id) {
                displayToast("danger", "No se pudo identificar el usuario a editar.");
                return;
            }
            await axios.put(usuarioUpdateUrl(id), payload);
            displayToast("success", "Usuario actualizado correctamente.");
        } else {
            const { data } = await axios.post(route("usuarios.store"), payload);
            displayToast("success", data.message || "Usuario registrado correctamente.");
        }
        closeAllModals();
        router.reload({ only: ["users", "vinculosDisponibles"] });
    } catch (e) {
        if (e.response?.status === 422) {
            fromAxios(e);
            displayToast(
                "danger",
                "Por favor, corrige los errores del formulario."
            );
        } else {
            displayToast(
                "danger",
                e.response?.data?.message || "Ocurrió un error inesperado."
            );
        }
    } finally {
        loading.value = false;
    }
}

async function submitDelete() {
    const id = resolveUsuarioId();
    if (!id) {
        displayToast("danger", "No se pudo identificar el usuario a dar de baja.");
        return;
    }

    loading.value = true;
    try {
        await axios.delete(usuarioDestroyUrl(id));
        displayToast("success", "Usuario dado de baja correctamente.");
        closeAllModals();
        router.reload({ only: ["users", "vinculosDisponibles"] });
    } catch (e) {
        displayToast(
            "danger",
            e.response?.data?.message || "Error al eliminar el usuario."
        );
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <AdminLayout title="Usuarios">
        <!-- Toast Notification -->
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType" closable>{{
                toastMsg
            }}</FwbToast>
        </div>

        <!-- Header & Actions -->
        <div class="vet-section-header">
            <h2 class="text-2xl font-semibold vet-page-title">Usuarios</h2>
            <FwbButton
                v-if="canCreateUsers"
                @click="openCreateModal"
                color="green"
                ><i class="fa-solid fa-plus mr-2"></i>Agregar Usuario</FwbButton
            >
        </div>

        <!-- Filters -->
        <form
            @submit.prevent="applyFilters"
            class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 vet-filter-panel"
        >
            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-1.5"
                    >Buscar por nombre, correo, CI o teléfono</label
                >
                <TextInput
                    v-model="filters.search_term"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="Escriba para buscar..."
                />
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5">Filtrar por rol</label>
                <select v-model="filters.role_id" class="w-full mt-1 rounded-md shadow-sm themed-input">
                    <option value="">Todos los roles</option>
                    <option v-for="role in rolesLista" :key="role.id" :value="role.id">
                        {{ role.name || role.nombre }}
                    </option>
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <FwbButton color="green" type="submit">Filtrar</FwbButton>
                <FwbButton color="alternative" @click.prevent="resetFilters"
                    >Limpiar</FwbButton
                >
            </div>
        </form>

        <!-- Users Table -->
        <div class="vet-panel p-0 overflow-hidden">
        <FwbTable class="vet-list-table">
            <FwbTableHead>
                <FwbTableHeadCell
                    >Nombre Completo</FwbTableHeadCell
                >
                <FwbTableHeadCell
                    >Correo</FwbTableHeadCell
                >
                <FwbTableHeadCell
                    >Teléfono</FwbTableHeadCell
                >
                <FwbTableHeadCell
                    >Rol</FwbTableHeadCell
                >
                <FwbTableHeadCell
                    >Estado</FwbTableHeadCell
                >
                <FwbTableHeadCell
                    >Actualizado</FwbTableHeadCell
                >
                <FwbTableHeadCell
                    ><span class="sr-only">Acciones</span></FwbTableHeadCell
                >
            </FwbTableHead>
            <FwbTableBody>
                <FwbTableRow v-if="!users?.data?.length"
                    ><FwbTableCell
                        colspan="7"
                        class="text-center py-8 vet-cell-muted"
                        >No se encontraron usuarios.</FwbTableCell
                    ></FwbTableRow
                >
                <FwbTableRow
                    v-for="user in users.data"
                    :key="user?.id ?? user?.email"
                >
                    <FwbTableCell class="vet-cell-primary">{{
                        user.full_name
                    }}</FwbTableCell>
                    <FwbTableCell class="vet-cell-muted">{{
                        user.email
                    }}</FwbTableCell>
                    <FwbTableCell class="vet-cell-muted">{{
                        user.telefono || "—"
                    }}</FwbTableCell>
                    <FwbTableCell class="vet-cell-primary">{{
                        user.role?.name || user.role?.nombre || "N/A"
                    }}</FwbTableCell>
                    <FwbTableCell>
                        <span
                            class="text-xs font-medium px-2 py-1 rounded-full"
                            :class="user.esta_activo !== false ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600'"
                        >
                            {{ user.esta_activo !== false ? "Activo" : "Baja" }}
                        </span>
                    </FwbTableCell>
                    <FwbTableCell class="vet-cell-muted">{{
                        user.updated_at
                    }}</FwbTableCell>
                    <FwbTableCell class="space-x-2 whitespace-nowrap">
                        <TableActionButtons
                            :can-view="true"
                            :can-edit="canEditUsers"
                            :can-delete="canDeleteUsers"
                            view-title="Ver detalles"
                            delete-title="Dar de baja"
                            @view="openViewModal(user)"
                            @edit="openEditModal(user)"
                            @delete="openDeleteModal(user)"
                        />
                    </FwbTableCell>
                </FwbTableRow>
            </FwbTableBody>
        </FwbTable>
        </div>
        <div v-if="users?.data?.length" class="flex justify-center my-4">
            <FwbPagination
                v-model="currentPage"
                :total-pages="users.last_page"
                large
            />
        </div>

        <!-- View Modal -->
        <FwbModal size="lg" v-if="isViewModal" @close="closeAllModals">
            <template #header
                ><h3 class="text-xl font-semibold themed-text-base">
                    Detalles del Usuario
                </h3></template
            >
            <template #body>
                <div
                    v-if="selectedUser"
                    class="space-y-4 text-sm themed-text-base p-2"
                >
                    <p><strong>Nombre:</strong> {{ selectedUser.full_name }}</p>
                    <p><strong>Correo:</strong> {{ selectedUser.email }}</p>
                    <p v-if="selectedUser.ci"><strong>CI:</strong> {{ selectedUser.ci }}</p>
                    <p v-if="selectedUser.telefono"><strong>Teléfono:</strong> {{ selectedUser.telefono }}</p>
                    <p v-if="selectedUser.fecha_nacimiento"><strong>Fecha de nacimiento:</strong> {{ selectedUser.fecha_nacimiento }}</p>
                    <p v-if="selectedUser.direccion"><strong>Dirección:</strong> {{ selectedUser.direccion }}</p>
                    <p v-if="selectedUser.especialidad"><strong>Especialidad:</strong> {{ selectedUser.especialidad }}</p>
                    <p>
                        <strong>Rol:</strong>
                        {{ selectedUser.role?.name || selectedUser.role?.nombre || "N/A" }}
                    </p>
                    <p>
                        <strong>Estado:</strong>
                        {{ selectedUser.esta_activo !== false ? "Activo" : "Baja" }}
                    </p>
                    <p>
                        <strong>Registrado:</strong>
                        {{ selectedUser.created_at }}
                    </p>
                    <p>
                        <strong>Actualizado:</strong>
                        {{ selectedUser.updated_at }}
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
            <template #header
                ><h3 class="themed-text-base">
                    Confirmar baja
                </h3></template
            >
            <template #body
                ><p class="text-center text-lg themed-text-base">
                    ¿Dar de baja a
                    <strong>{{ selectedUser?.full_name }}</strong
                    >? Podrás reactivarlo editando su estado.
                </p></template
            >
            <template #footer
                ><div class="flex justify-center w-full">
                    <FwbButton @click="closeAllModals" color="alternative"
                        >Cancelar</FwbButton
                    ><FwbButton
                        @click="submitDelete"
                        color="red"
                        :loading="loading"
                        class="ml-2"
                        >Dar de baja</FwbButton
                    >
                </div></template
            >
        </FwbModal>

        <!-- Create/Edit Modal -->
        <FwbModal size="2xl" v-if="isCreateOrEditModal" @close="closeAllModals">
            <template #header
                ><h3 class="text-xl font-semibold themed-text-base">
                    {{ modalMode === "edit" ? "Editar" : "Registrar" }} Usuario
                </h3></template
            >
            <template #body>
                <form
                    class="space-y-4 themed-text-base p-2"
                    @submit.prevent="submitForm"
                >
                    <div>
                        <InputLabel value="Rol" />
                        <select
                            v-model="form.role_id"
                            class="w-full mt-1 rounded-md shadow-sm themed-input"
                        >
                            <option value="" disabled>Seleccione un rol</option>
                            <option
                                v-for="role in rolesDisponibles"
                                :key="role.id"
                                :value="role.id"
                            >
                                {{ role.name || role.nombre }}
                            </option>
                        </select>
                        <InputError :message="get('role_id')" />
                    </div>

                    <div
                        v-if="modalMode === 'create' && requiereVinculo"
                        class="rounded-lg border themed-border p-3"
                    >
                        <InputLabel :value="etiquetaVinculos" />
                        <select
                            v-model="form.vinculo_id"
                            class="w-full mt-1 rounded-md shadow-sm themed-input"
                            @change="onVinculoChange"
                        >
                            <option value="">
                                {{
                                    listaVinculos.length
                                        ? "Seleccione de la lista (recomendado)"
                                        : "No hay fichas sin cuenta"
                                }}
                            </option>
                            <option
                                v-for="vinculo in listaVinculos"
                                :key="vinculo.id"
                                :value="vinculo.id"
                            >
                                {{ vinculo.label }}
                            </option>
                        </select>
                        
                    </div>

                   

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Nombre(s)" /><TextInput
                                v-model="form.first_name"
                                class="mt-1 w-full themed-input"
                            /><InputError
                                :message="get('first_name')"
                            />
                        </div>
                        <div>
                            <InputLabel value="Apellido(s)" /><TextInput
                                v-model="form.last_name"
                                class="mt-1 w-full themed-input"
                            /><InputError
                                :message="get('last_name')"
                            />
                        </div>
                    </div>
                    <div>
                        <InputLabel value="Correo Electrónico" /><TextInput
                            v-model="form.email"
                            type="email"
                            class="mt-1 w-full themed-input"
                        /><InputError :message="get('email')" />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="CI" />
                            <TextInput v-model="form.ci" class="mt-1 w-full themed-input" />
                            <InputError :message="get('ci')" />
                        </div>
                        <div>
                            <InputLabel value="Teléfono" />
                            <TextInput v-model="form.telefono" class="mt-1 w-full themed-input" />
                            <InputError :message="get('telefono')" />
                        </div>
                        <div>
                            <InputLabel value="Fecha de nacimiento" />
                            <TextInput v-model="form.fecha_nacimiento" type="date" class="mt-1 w-full themed-input" />
                            <InputError :message="get('fecha_nacimiento')" />
                        </div>
                        <div>
                            <InputLabel value="Dirección" />
                            <TextInput v-model="form.direccion" class="mt-1 w-full themed-input" />
                            <InputError :message="get('direccion')" />
                        </div>
                        <div v-if="nombreRolSeleccionado === 'veterinario'" class="md:col-span-2">
                            <InputLabel value="Especialidad" />
                            <TextInput v-model="form.especialidad" class="mt-1 w-full themed-input" />
                            <InputError :message="get('especialidad')" />
                        </div>
                    </div>
                    <div v-if="modalMode === 'edit'">
                        <FwbToggle
                            v-model="form.esta_activo"
                            label="Usuario activo"
                        />
                    </div>
                    <hr class="themed-border" />
                    <div>
                        <FwbToggle
                            v-model="autoPassword"
                            label="Generar/Mantener contraseña automáticamente"
                        />
                        <p v-if="autoPassword && modalMode === 'create'" class="text-xs text-gray-500 mt-2">
                            Si no define contraseña, se generará una temporal: nombre + inicial del apellido + año actual
                            (ej. Juan Pérez → JuanP2026).
                        </p>
                    </div>
                    <div
                        v-if="!autoPassword"
                        class="grid grid-cols-1 md:grid-cols-2 gap-4"
                    >
                        <div class="relative">
                            <InputLabel value="Contraseña" />
                            <TextInput
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                class="mt-1 w-full pr-10 themed-input"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 top-6 pr-3 flex items-center text-sm leading-5 themed-text-muted"
                            >
                                <i
                                    class="fa-solid"
                                    :class="{
                                        'fa-eye': !showPassword,
                                        'fa-eye-slash': showPassword,
                                    }"
                                ></i>
                            </button>
                            <InputError :message="get('password')" />
                        </div>
                        <div class="relative">
                            <InputLabel value="Confirmar Contraseña" />
                            <TextInput
                                v-model="form.password_confirmation"
                                :type="showPassword ? 'text' : 'password'"
                                class="mt-1 w-full themed-input"
                            />
                            <InputError
                                :message="get('password_confirmation')"
                            />
                        </div>
                    </div>
                    <p
                        v-if="modalMode === 'edit' && autoPassword"
                        class="text-xs themed-text-muted"
                    >
                        La contraseña actual se mantendrá sin cambios.
                    </p>
                </form>
            </template>
            <template #footer
                ><div class="flex justify-end">
                    <FwbButton @click="closeAllModals" color="alternative"
                        >Cancelar</FwbButton
                    ><FwbButton
                        @click="submitForm"
                        color="green"
                        :loading="loading"
                        class="ml-2"
                        >Guardar</FwbButton
                    >
                </div></template
            >
        </FwbModal>
    </AdminLayout>
</template>

<style scoped>
.themed-text-base {
    color: var(--color-text-base);
}
.themed-text-muted {
    color: var(--color-text-muted);
}

:deep(.fwb-modal-header),
:deep(.fwb-modal-body),
:deep(.fwb-modal-footer) {
    background-color: var(--color-surface);
    color: var(--color-text-base);
}

:deep(.fwb-modal-header) {
    border-bottom: 1px solid var(--color-border);
}

:deep(.fwb-modal-footer) {
    border-top: 1px solid var(--color-border);
}
</style>
