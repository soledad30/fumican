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
import RolEnum from "@/Utils/Enums/RolEnum";

const route = inject("route");

import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import TableActionButtons from "@/Components/TableActionButtons.vue";

const props = defineProps({
    roles: Object,
    permissions: Array,
    filters: Object,
});

const filters = ref({ search_term: props.filters?.search_term || "" });

function applyFilters() {
    router.get(route("roles.index"), filters.value, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    filters.value = { search_term: "" };
    router.get(route("roles.index"));
}

const currentPage = ref(props.roles.current_page || 1);
watch(currentPage, (newPage) => {
    router.get(
        route("roles.index"),
        { ...filters.value, page: newPage },
        { preserveState: true, replace: true }
    );
});

const loading = ref(false);
const showToast = ref(false);
const toastMsg = ref("");
const toastType = ref("success");

const modalMode = ref("create");
const isCreateOrEditModal = ref(false);
const isViewModal = ref(false);
const selectedRole = ref(null);
const editingRoleId = ref(null);

const defaultFormState = { id: null, name: "", permissions: [] };
const form = ref({ ...defaultFormState });
const formErrors = ref({});
const allPermissions = ref([]);

const page = usePage();
const { puede, esAdmin } = usePermisos();
const canCreateRoles = computed(() => esAdmin.value || puede("crear roles"));
const canEditRoles = computed(() => esAdmin.value || puede("editar roles"));
const canViewRoles = true;
const esNombreRolProtegido = computed(() => {
    const nombre = selectedRole.value?.name ?? selectedRole.value?.nombre;
    return nombre === RolEnum.PROPIETARIO || nombre === RolEnum.ADMINISTRADOR;
});

function rolePermissions(role) {
    return role?.permisos ?? role?.permissions ?? [];
}

const ACCENTOS = {
    medicas: "médicas",
    bitacora: "bitácora",
    categorias: "categorías",
};

const GRUPOS_PERMISOS = [
    { titulo: "Sistema", prefijos: ["administrar", "ver_reportes"] },
    { titulo: "Usuarios y roles", prefijos: ["listar_usuarios", "crear_usuarios", "editar_usuarios", "ver_usuarios", "listar_roles", "crear_roles", "editar_roles", "eliminar_roles"] },
    { titulo: "Clientes", prefijos: ["listar_clientes", "crear_clientes", "editar_clientes", "eliminar_clientes", "ver_clientes"] },
    { titulo: "Mascotas", prefijos: ["listar_mascotas", "crear_mascotas", "editar_mascotas", "eliminar_mascotas", "ver_mascotas"] },
    { titulo: "Veterinarios", prefijos: ["listar_veterinarios", "gestionar_veterinarios"] },
    { titulo: "Consultas", prefijos: ["listar_consultas", "crear_consultas", "editar_consultas", "eliminar_consultas", "reservar_citas"] },
    { titulo: "Historial / vacunas", prefijos: ["listar_historial", "gestionar_historial"] },
    { titulo: "Servicios y pagos", prefijos: ["listar_servicios", "gestionar_servicios", "listar_pagos", "gestionar_pagos"] },
    { titulo: "Ventas e inventario", prefijos: ["listar_productos", "gestionar_productos", "listar_inventarios", "gestionar_inventarios", "listar_ventas", "gestionar_ventas"] },
    { titulo: "Compras", prefijos: ["listar_compras", "gestionar_compras"] },
];

function formatPermiso(nombre) {
    if (!nombre) return "";
    let label = nombre.replace(/_/g, " ").trim().toLowerCase();
    for (const [from, to] of Object.entries(ACCENTOS)) {
        label = label.replace(new RegExp(`\\b${from}\\b`, "g"), to);
    }
    return label.charAt(0).toUpperCase() + label.slice(1);
}

function formatRol(nombre) {
    if (!nombre) return "";
    return nombre.charAt(0).toUpperCase() + nombre.slice(1);
}

function grupoPermiso(nombre) {
    const n = (nombre ?? "").toLowerCase();
    for (const grupo of GRUPOS_PERMISOS) {
        if (grupo.prefijos.some((p) => n === p || n.startsWith(p + "_"))) {
            return grupo.titulo;
        }
    }
    return "Otros";
}

const permisosAgrupados = computed(() => {
    const mapa = new Map();
    for (const perm of allPermissions.value) {
        const titulo = grupoPermiso(perm.name ?? perm.nombre);
        if (!mapa.has(titulo)) {
            mapa.set(titulo, []);
        }
        mapa.get(titulo).push(perm);
    }

    const orden = GRUPOS_PERMISOS.map((g) => g.titulo).concat(["Otros"]);
    return orden
        .filter((titulo) => mapa.has(titulo))
        .map((titulo) => ({
            titulo,
            items: mapa.get(titulo).sort((a, b) =>
                (a.name ?? a.nombre ?? "").localeCompare(b.name ?? b.nombre ?? "")
            ),
        }));
});

function displayToast(type, message) {
    toastType.value = type;
    toastMsg.value = message;
    showToast.value = true;
    setTimeout(() => (showToast.value = false), 3000);
}

function setupPermissions(rolePermissions = []) {
    const asignados = new Set(
        rolePermissions.map((rp) => Number(rp.id)).filter((id) => !Number.isNaN(id))
    );
    allPermissions.value = props.permissions.map((p) => ({
        ...p,
        checked: asignados.has(Number(p.id)),
    }));
}

function openCreateModal() {
    modalMode.value = "create";
    selectedRole.value = null;
    editingRoleId.value = null;
    form.value = { ...defaultFormState };
    setupPermissions();
    formErrors.value = {};
    isCreateOrEditModal.value = true;
}

function openEditModal(role) {
    modalMode.value = "edit";
    selectedRole.value = role;
    editingRoleId.value = role?.id ?? null;
    form.value = {
        ...defaultFormState,
        id: role.id,
        name: role.name ?? role.nombre ?? "",
    };
    setupPermissions(rolePermissions(role));
    formErrors.value = {};
    isCreateOrEditModal.value = true;
}

function openViewModal(role) {
    modalMode.value = "view";
    selectedRole.value = role;
    setupPermissions(rolePermissions(role));
    isViewModal.value = true;
}

function closeAllModals() {
    isCreateOrEditModal.value = false;
    isViewModal.value = false;
    editingRoleId.value = null;
}

function resolveRoleId() {
    const id = editingRoleId.value ?? form.value.id ?? selectedRole.value?.id;
    return id != null && id !== "" ? Number(id) : null;
}

function rolUpdateUrl(id) {
    return route("roles.update", { id });
}

async function submitForm() {
    loading.value = true;
    formErrors.value = {};

    const payload = {
        name: form.value.name,
        permissions: allPermissions.value
            .filter((p) => p.checked)
            .map((p) => p.id),
    };

    try {
        if (modalMode.value === "edit") {
            const id = resolveRoleId();
            if (!id) {
                displayToast("danger", "No se pudo identificar el rol a editar.");
                return;
            }
            await axios.put(rolUpdateUrl(id), payload);
            displayToast("success", "Rol actualizado correctamente.");
        } else {
            await axios.post(route("roles.store"), payload);
            displayToast("success", "Rol registrado correctamente.");
        }
        closeAllModals();
        router.reload({ only: ["roles"] });
    } catch (e) {
        if (e.response?.status === 422) {
            formErrors.value = e.response.data.errors;
            displayToast("danger", "Por favor, corrige los errores.");
        } else {
            displayToast(
                "danger",
                e.response?.data?.message ||
                    (e.response?.status === 405
                        ? "No se pudo guardar el rol. Recargue la página e intente de nuevo."
                        : "Ocurrió un error inesperado.")
            );
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <AdminLayout title="Roles y Permisos">
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType" closable>{{
                toastMsg
            }}</FwbToast>
        </div>

        <div class="vet-section-header">
            <h2 class="text-2xl font-semibold vet-page-title">Roles y Permisos</h2>
            <FwbButton
                v-if="canCreateRoles"
                @click="openCreateModal"
                color="green"
            >
                <i class="fa-solid fa-plus mr-2"></i> Agregar Rol
            </FwbButton>
        </div>

        <form
            @submit.prevent="applyFilters"
            class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 vet-filter-panel"
        >
            <div class="md:col-span-3">
                <label class="block text-sm mb-1.5">Buscar por nombre de rol</label>
                <TextInput
                    v-model="filters.search_term"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="Escriba un nombre..."
                />
            </div>
            <div class="flex items-end gap-2">
                <FwbButton color="green" type="submit">Filtrar</FwbButton>
                <FwbButton color="alternative" @click.prevent="resetFilters">
                    Limpiar
                </FwbButton>
            </div>
        </form>

        <div class="vet-panel p-0 overflow-hidden">
            <FwbTable class="vet-list-table">
                <FwbTableHead>
                    <FwbTableHeadCell>Nombre del rol</FwbTableHeadCell>
                    <FwbTableHeadCell>Fecha de creación</FwbTableHeadCell>
                    <FwbTableHeadCell>Última modificación</FwbTableHeadCell>
                    <FwbTableHeadCell>
                        <span class="sr-only">Acciones</span>
                    </FwbTableHeadCell>
                </FwbTableHead>
                <FwbTableBody>
                    <FwbTableRow v-if="!roles.data.length">
                        <FwbTableCell colspan="4" class="text-center py-8 vet-cell-muted">
                            No se encontraron roles.
                        </FwbTableCell>
                    </FwbTableRow>
                    <FwbTableRow v-for="role in roles.data" :key="role.id">
                        <FwbTableCell class="vet-cell-primary">
                            <i class="fa-solid fa-user-shield mr-2 text-emerald-500 opacity-70"></i>
                            {{ formatRol(role.name ?? role.nombre) }}
                        </FwbTableCell>
                        <FwbTableCell class="vet-cell-muted">{{ role.created_at }}</FwbTableCell>
                        <FwbTableCell class="vet-cell-muted">{{ role.updated_at }}</FwbTableCell>
                        <FwbTableCell class="space-x-2 whitespace-nowrap">
                            <TableActionButtons
                                :can-view="canViewRoles"
                                :can-edit="canEditRoles"
                                :can-delete="false"
                                view-title="Ver permisos"
                                edit-title="Editar rol"
                                @view="openViewModal(role)"
                                @edit="openEditModal(role)"
                            />
                        </FwbTableCell>
                    </FwbTableRow>
                </FwbTableBody>
            </FwbTable>
        </div>

        <div v-if="roles.data.length" class="flex justify-center my-4">
            <FwbPagination
                v-model="currentPage"
                :total-items="roles.total"
                :per-page="roles.per_page"
                large
            />
        </div>

        <FwbModal
            size="2xl"
            v-if="isCreateOrEditModal || isViewModal"
            @close="closeAllModals"
        >
            <template #header>
                <h3 class="text-xl font-semibold vet-page-title" v-if="modalMode === 'create'">
                    Crear nuevo rol
                </h3>
                <h3 class="text-xl font-semibold vet-page-title" v-if="modalMode === 'edit'">
                    Editar rol: {{ formatRol(selectedRole?.name ?? selectedRole?.nombre) }}
                </h3>
                <h3 class="text-xl font-semibold vet-page-title" v-if="modalMode === 'view'">
                    Permisos del rol: {{ formatRol(selectedRole?.name ?? selectedRole?.nombre) }}
                </h3>
            </template>
            <template #body>
                <form class="space-y-4 p-2" @submit.prevent="submitForm">
                    <div>
                        <InputLabel value="Nombre del rol" class="!text-emerald-800" />
                        <TextInput
                            v-model="form.name"
                            class="mt-1 w-full"
                            :disabled="modalMode === 'view' || esNombreRolProtegido"
                        />
                        <InputError :message="formErrors.name?.[0]" />
                    </div>
                    <hr class="vet-modal-divider" />
                    <div>
                        <h4 class="font-semibold text-emerald-900 mb-2">
                            Permisos asignados
                        </h4>
                        <div class="h-80 overflow-y-auto space-y-4 pr-2">
                            <div
                                v-for="grupo in permisosAgrupados"
                                :key="grupo.titulo"
                            >
                                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 mb-2">
                                    {{ grupo.titulo }}
                                </p>
                                <div class="space-y-2">
                                    <div
                                        v-for="permission in grupo.items"
                                        :key="permission.id"
                                        class="vet-perm-row"
                                        :class="{ 'vet-perm-row--active': permission.checked }"
                                    >
                                        <span>{{ formatPermiso(permission.name ?? permission.nombre) }}</span>
                                        <FwbToggle
                                            v-model="permission.checked"
                                            :disabled="modalMode === 'view'"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <InputError :message="formErrors.permissions?.[0]" />
                    </div>
                </form>
            </template>
            <template #footer>
                <div class="flex justify-end w-full gap-2">
                    <FwbButton @click="closeAllModals" color="alternative">
                        Cerrar
                    </FwbButton>
                    <FwbButton
                        v-if="modalMode !== 'view'"
                        @click="submitForm"
                        color="green"
                        :loading="loading"
                    >
                        Guardar
                    </FwbButton>
                </div>
            </template>
        </FwbModal>
    </AdminLayout>
</template>
