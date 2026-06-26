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
    FwbTextarea,
    FwbBadge,
} from "flowbite-vue";
import { computed, ref, watch, inject } from "vue";
import axios from "axios";
import { usePermisos } from "@/Composables/usePermisos";
import TableActionButtons from "@/Components/TableActionButtons.vue";

const route = inject("route");

// Components
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import FormSectionTitle from "@/Components/Forms/FormSectionTitle.vue";
import SearchModal from "@/Components/Modals/SearchModal.vue";
import SearchUser from "@/Components/Icons/Svg/SearchUser.vue";
import { useDebouncedRef } from "@/Utils/debouncedRef";

// --- PROPS & PAGINATION & FILTERS ---
const props = defineProps({
    medicalConsultations: Object,
    servicios: Array,
    estadosConsulta: Object,
    metodosPago: { type: Object, default: () => ({}) },
    tiposPago: { type: Object, default: () => ({}) },
    filters: Object,
});

const currentPage = ref(props.medicalConsultations.current_page || 1);
watch(currentPage, (newPage) => {
    const params = { ...filters.value, page: newPage };
    router.get(route("consultas-medicas.search"), params, {
        preserveState: true,
        replace: true,
    });
});

const filters = ref({
    search_term: props.filters.search_term || "",
    date_from: props.filters.date_from || "",
    date_to: props.filters.date_to || "",
    estado: props.filters.estado || "",
});

function applyFilters() {
    router.get(route("consultas-medicas.search"), filters.value, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    filters.value = { search_term: "", date_from: "", date_to: "", estado: "" };
    router.get(route("consultas-medicas.index"));
}

// --- STATE MANAGEMENT ---
const loading = ref(false);
const showToast = ref(false);
const toastMsg = ref("");
const toastType = ref("success");

// --- MODALS ---
const isCreateOrEditModal = ref(false);
const isDeleteModal = ref(false);
const isEmergenciaModal = ref(false);
const isPagoModal = ref(false);
const pendienteCambioEstado = ref(null);
const pagoConsulta = ref(null);
const pagoForm = ref({
    monto: 0,
    tipo_pago: "contado",
    metodo_pago: "efectivo",
    id_transaccion_externa: "",
    num_cuotas: 2,
});
const pagoQrImage = ref("");
const pagoQrTransaccion = ref("");
const pagoQrVerificando = ref(false);
let pagoQrInterval = null;
const isSearchPetModal = ref(false);
const isViewModal = ref(false);
const modalMode = ref("create");

const selectedConsultation = ref(null);
const editingConsultaId = ref(null);
const selectedPet = ref(null);
const resumenClinico = ref(null);
const modoConsulta = ref("inicial");

const esModoSeguimiento = computed(
    () => modalMode.value === "create" && modoConsulta.value === "seguimiento"
);

// --- FORM ---
const defaultFormState = {
    id: null,
    reason: "",
    pet_id: "",
    dewormed_at: "",
    previous_illnesses: "",
    previous_interventions: "",
    general_condition: "Bueno",
    appetite: [],
    hydratation: [],
    mucosa: [],
    weight: "",
    digestive_system: "",
    genitourinary_system: "",
    respiratory_system: "",
    temperature: "",
    heart_rate: "",
    respiratory_rate: "",
    clinical_observation: "",
    complementary_tests: "",
    presumptive_diagnosis: "",
    confirmatory_diagnosis: "",
    consultation_fee: "",
    treatment: "",
    service_id: "",
    estado: "completada",
};
const form = ref({ ...defaultFormState });
const formErrors = ref({});

// --- Mascota SEARCH ---
const search = useDebouncedRef("", 400); // 400ms debounce
const isFetchingData = ref(false);
const petsList = ref([]);

// --- HELPERS & PERMISSIONS ---
const isEmptyData = computed(
    () => props.medicalConsultations.data.length === 0
);
const page = usePage();
const { puede } = usePermisos();
const canCreateMedCons = computed(() => puede("crear consultas medicas"));
const canEditMedCons = computed(() => puede("editar consultas medicas"));
const canDeleteMedCons = computed(() => puede("eliminar consultas medicas"));
const canManagePagos = computed(() => puede("gestionar pagos") || puede("crear pagos"));

watch(() => form.value.service_id, (id) => {
    if (!id) return;
    const s = props.servicios.find((x) => String(x.id) === String(id));
    if (s && !form.value.consultation_fee) {
        form.value.consultation_fee = s.precio;
    }
});

const estadoBadge = {
    reservada: "yellow",
    en_atencion: "blue",
    completada: "green",
    cancelada: "red",
    no_asistio: "dark",
};

const transicionesRapidas = {
    reservada: [
        { estado: "en_atencion", label: "Atender", color: "blue" },
        { estado: "completada", label: "Completar", color: "green" },
        { estado: "cancelada", label: "Cancelar", color: "red" },
        { estado: "no_asistio", label: "No asistió", color: "dark" },
    ],
    en_atencion: [
        { estado: "completada", label: "Completar", color: "green" },
        { estado: "cancelada", label: "Cancelar", color: "red" },
    ],
};

function fechaHoyYmd() {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${y}-${m}-${day}`;
}

function normalizarFechaYmd(fecha) {
    if (!fecha) return null;
    const soloFecha = String(fecha).slice(0, 10);
    const partes = soloFecha.split("-");
    if (partes.length !== 3) return soloFecha;
    if (partes[0].length === 4) return soloFecha;
    return `${partes[2]}-${partes[1]}-${partes[0]}`;
}

function esFechaAtencionHoy(consultation) {
    const fechaReserva = normalizarFechaYmd(consultation.fecha);
    if (!fechaReserva) return true;
    return fechaReserva === fechaHoyYmd();
}

function solicitarCambiarEstado(consultation, nuevoEstado) {
    if (nuevoEstado === "en_atencion" && !esFechaAtencionHoy(consultation)) {
        pendienteCambioEstado.value = { consultation, nuevoEstado };
        isEmergenciaModal.value = true;
        return;
    }
    cambiarEstado(consultation, nuevoEstado);
}

function cerrarEmergenciaModal() {
    isEmergenciaModal.value = false;
    pendienteCambioEstado.value = null;
}

function confirmarAtencionEmergencia() {
    if (!pendienteCambioEstado.value) return;
    const { consultation, nuevoEstado } = pendienteCambioEstado.value;
    cerrarEmergenciaModal();
    cambiarEstado(consultation, nuevoEstado, true);
}

function resolveConsultaId(consultation) {
    const id = consultation?.id ?? editingConsultaId.value ?? selectedConsultation.value?.id;
    return id != null && id !== "" ? Number(id) : null;
}

function urlCambiarEstadoConsulta(id) {
    return `/servicios/consultas-medicas/${id}/estado`;
}

function urlActualizarConsulta(id) {
    return route("consultas-medicas.update", { id });
}

function urlEliminarConsulta(id) {
    return route("consultas-medicas.destroy", { id });
}

async function cambiarEstado(consultation, nuevoEstado, emergencia = false) {
    const consultaId = resolveConsultaId(consultation);
    if (!consultaId) {
        displayToast("danger", "No se pudo identificar la consulta.");
        return;
    }

    loading.value = true;
    try {
        const { data } = await axios.patch(
            urlCambiarEstadoConsulta(consultaId),
            { estado: nuevoEstado, emergencia }
        );
        displayToast("success", data.message);
        router.reload({ only: ["medicalConsultations"] });
    } catch (e) {
        const msg =
            e.response?.data?.message ||
            (e.response?.status === 405
                ? "No se pudo cambiar el estado de la consulta."
                : "No se pudo cambiar el estado.");
        displayToast("danger", msg);
    } finally {
        loading.value = false;
    }
}

function saldoConsulta(consultation) {
    const saldo = consultation.saldo_pendiente ?? consultation.saldoPendiente;
    if (saldo != null) return Number(saldo);
    const total = Number(consultation.costo_consulta ?? consultation.consultation_fee ?? 0);
    const pagado = Number(consultation.monto_pagado ?? 0);
    return Math.max(0, total - pagado);
}

function datosClienteQr(consultation) {
    const cliente =
        consultation?.mascota?.propietario ??
        consultation?.mascota?.cliente ??
        consultation?.mascota?.owner;
    const nombre =
        consultation?.pet_owner ||
        `${cliente?.nombre || ""} ${cliente?.apellido || ""}`.trim() ||
        "Cliente Fumican";
    const phone = String(cliente?.telefono || cliente?.ci || "70000000");
    const email = cliente?.email || "pagos@fumican.bo";
    const servicio =
        consultation?.servicio?.nombre ||
        consultation?.reason ||
        `Consulta #${consultation?.id}`;

    return { name: nombre, phone, email, descripcion: servicio };
}

function limpiarQrPago() {
    if (pagoQrInterval) {
        clearInterval(pagoQrInterval);
        pagoQrInterval = null;
    }
    pagoQrImage.value = "";
    pagoQrTransaccion.value = "";
    pagoQrVerificando.value = false;
}

function abrirPagoModal(consultation) {
    const saldo = saldoConsulta(consultation);
    if (saldo <= 0) {
        displayToast("success", "Esta consulta no tiene saldo pendiente.");
        return;
    }
    limpiarQrPago();
    pagoConsulta.value = consultation;
    pagoForm.value = {
        monto: saldo,
        tipo_pago: "contado",
        metodo_pago: "efectivo",
        id_transaccion_externa: "",
        num_cuotas: 2,
    };
    isPagoModal.value = true;
}

watch(() => pagoForm.value.metodo_pago, (metodo) => {
    if (metodo !== "qr") {
        limpiarQrPago();
    }
});

function cerrarPagoModal() {
    limpiarQrPago();
    isPagoModal.value = false;
    pagoConsulta.value = null;
}

async function generarQrPago() {
    const monto = Number(pagoForm.value.monto);
    if (!monto || monto <= 0) {
        displayToast("danger", "Indique un monto válido para generar el QR.");
        return false;
    }

    const cliente = datosClienteQr(pagoConsulta.value);
    try {
        const { data } = await axios.post("/api/generar-qr", {
            ...cliente,
            monto,
            serviceId: pagoConsulta.value?.servicio_id || pagoConsulta.value?.service_id,
        });
        if (!data.success || !data.qrImage) {
            displayToast("danger", data.message || "No se pudo generar el código QR.");
            return false;
        }
        pagoQrImage.value = `data:image/png;base64,${data.qrImage}`;
        pagoQrTransaccion.value = data.numeroTransaccion;
        pagoForm.value.id_transaccion_externa = data.numeroTransaccion || "";
        return true;
    } catch (e) {
        displayToast(
            "danger",
            e.response?.data?.message || "Error al generar el código QR."
        );
        return false;
    }
}

async function verificarQrPago() {
    if (!pagoQrTransaccion.value) return false;
    try {
        const { data } = await axios.post("/api/verificar-pago", {
            numeroTransaccion: pagoQrTransaccion.value,
        });
        return data.data?.EstadoTransaccion === 5;
    } catch {
        return false;
    }
}

function iniciarVerificacionQrPago() {
    if (pagoQrInterval) clearInterval(pagoQrInterval);
    pagoQrVerificando.value = true;
    let intentos = 0;
    const maxIntentos = 12;

    pagoQrInterval = setInterval(async () => {
        intentos++;
        const pagado = await verificarQrPago();
        if (pagado) {
            clearInterval(pagoQrInterval);
            pagoQrInterval = null;
            pagoQrVerificando.value = false;
            await guardarPagoConsulta();
            return;
        }
        if (intentos >= maxIntentos) {
            clearInterval(pagoQrInterval);
            pagoQrInterval = null;
            pagoQrVerificando.value = false;
            displayToast(
                "danger",
                "El pago QR no fue confirmado. Escanee el código e intente de nuevo."
            );
        }
    }, 5000);
}

async function guardarPagoConsulta() {
    if (!pagoConsulta.value?.id) return;
    loading.value = true;
    try {
        const payload = {
            consulta_id: pagoConsulta.value.id,
            monto: pagoForm.value.monto,
            tipo_pago: pagoForm.value.tipo_pago,
            metodo_pago: pagoForm.value.metodo_pago,
            concepto_pago: "saldo",
            id_transaccion_externa: pagoForm.value.id_transaccion_externa || null,
        };
        if (pagoForm.value.tipo_pago === "credito") {
            payload.num_cuotas = pagoForm.value.num_cuotas;
        }
        const { data } = await axios.post(route("pagos.store"), payload);
        displayToast("success", data.message || "Pago registrado correctamente.");
        cerrarPagoModal();
        router.reload({ only: ["medicalConsultations"] });
    } catch (e) {
        displayToast(
            "danger",
            e.response?.data?.message ||
                e.response?.data?.errors?.monto?.[0] ||
                "No se pudo registrar el pago."
        );
    } finally {
        loading.value = false;
    }
}

async function registrarPagoConsulta() {
    if (!pagoConsulta.value?.id) return;

    if (pagoForm.value.metodo_pago === "qr") {
        if (!pagoQrImage.value) {
            loading.value = true;
            const generado = await generarQrPago();
            loading.value = false;
            if (generado) {
                displayToast("success", "Escanee el QR con su app bancaria.");
                iniciarVerificacionQrPago();
            }
            return;
        }
        if (!pagoQrVerificando.value) {
            loading.value = true;
            const pagado = await verificarQrPago();
            loading.value = false;
            if (pagado) {
                await guardarPagoConsulta();
            } else {
                displayToast("danger", "Pago aún no confirmado. Verificando...");
                iniciarVerificacionQrPago();
            }
        }
        return;
    }

    await guardarPagoConsulta();
}

// --- FUNCTIONS ---
function displayToast(type, message) {
    toastType.value = type;
    toastMsg.value = message;
    showToast.value = true;
    setTimeout(() => (showToast.value = false), 3000);
}

function prepareFormData(data) {
    const joinOrDefault = (val, def = "Normal") =>
        Array.isArray(val) ? (val.length ? val.join(", ") : def) : val || def;

    return {
        ...data,
        appetite: joinOrDefault(data.appetite),
        hydratation: joinOrDefault(data.hydratation),
        mucosa: joinOrDefault(data.mucosa),
        modo_consulta: esModoSeguimiento.value ? "seguimiento" : "inicial",
        veterinarian_id: page.props.auth.user.id,
    };
}

function openCreateModal() {
    modalMode.value = "create";
    editingConsultaId.value = null;
    form.value = { ...defaultFormState };
    selectedPet.value = null;
    resumenClinico.value = null;
    modoConsulta.value = "inicial";
    formErrors.value = {};
    isCreateOrEditModal.value = true;
}

function openEditModal(consultation) {
    modalMode.value = "edit";
    selectedConsultation.value = consultation;
    editingConsultaId.value = consultation?.id ?? null;
    formErrors.value = {};

    form.value = {
        ...defaultFormState,
        ...consultation,
        id: consultation.id,
        appetite: consultation.appetite
            ? consultation.appetite.split(", ")
            : [],
        hydratation: consultation.hydratation
            ? consultation.hydratation.split(", ")
            : [],
        mucosa: consultation.mucosa ? consultation.mucosa.split(", ") : [],
        service_id: consultation.service_id || consultation.servicio_id || "",
        estado: consultation.estado || "completada",
    };

    if (consultation.pet) {
        const pet = consultation.pet;
        selectedPet.value = {
            id: pet.id,
            name: pet.name,
            owner: pet.owner,
            owner_full_name: `${pet.owner?.first_name ?? ''} ${pet.owner?.last_name ?? ''}`.trim(),
            specie_and_breed: `${pet.breed?.specie?.name ?? ''} - ${pet.breed?.name ?? ''}`.trim(),
        };
    } else {
        selectedPet.value = null;
    }

    isCreateOrEditModal.value = true;
}

function openViewModal(consultation) {
    selectedConsultation.value = consultation;
    isViewModal.value = true;
}

function openDeleteModal(consultation) {
    selectedConsultation.value = consultation;
    editingConsultaId.value = consultation?.id ?? null;
    isDeleteModal.value = true;
}

function closeAllModals() {
    isCreateOrEditModal.value = false;
    isDeleteModal.value = false;
    isEmergenciaModal.value = false;
    isSearchPetModal.value = false;
    isViewModal.value = false;
    selectedConsultation.value = null;
    editingConsultaId.value = null;
    selectedPet.value = null;
    pendienteCambioEstado.value = null;
}

// CORRECCIÓN: Apuntar a la ruta de autocompletado de mascotas.
watch(search, async (value) => {
    if (value.length < 2) {
        // Buscar a partir de 2 caracteres
        petsList.value = [];
        return;
    }
    isFetchingData.value = true;
    try {
        // La ruta correcta es 'pets.autocomplete' que devuelve JSON
        const response = await axios.get(route("mascotas.autocomplete"), {
            params: { search: value },
        });
        petsList.value = response.data;
    } catch (error) {
        console.error("Error searching pets:", error);
        displayToast("danger", "No se pudieron cargar las mascotas.");
    } finally {
        isFetchingData.value = false;
    }
});

function handleSelectPet(pet) {
    selectedPet.value = {
        id: pet.id,
        name: pet.name,
        owner: pet.owner,
        owner_full_name: `${pet.owner?.first_name} ${pet.owner?.last_name}`,
        specie_and_breed: `${pet.breed?.specie?.name} - ${pet.breed?.name}`,
    };
    form.value.pet_id = pet.id;
    formErrors.value.pet_id = null;
    isSearchPetModal.value = false;
    search.value = "";
    petsList.value = [];
    cargarResumenClinico(pet.id);
}

async function cargarResumenClinico(petId) {
    try {
        const { data } = await axios.get(route("mascotas.resumen-clinico", petId));
        resumenClinico.value = data;
        modoConsulta.value = data.es_paciente_nuevo ? "inicial" : "seguimiento";
        if (data.peso_actual) {
            form.value.weight = data.peso_actual;
        }
    } catch {
        resumenClinico.value = null;
        modoConsulta.value = "inicial";
    }
}

// MEJORA: Unificar la lógica de envío para evitar duplicar código.
async function submitForm() {
    loading.value = true;
    formErrors.value = {};
    const payload = prepareFormData(form.value);

    const isEditing = modalMode.value === "edit";
    const consultaId = resolveConsultaId(selectedConsultation.value);
    const url = isEditing
        ? urlActualizarConsulta(consultaId)
        : route("consultas-medicas.store");
    const method = isEditing ? "put" : "post";

    if (isEditing && !consultaId) {
        displayToast("danger", "No se pudo identificar la consulta a editar.");
        loading.value = false;
        return;
    }

    try {
        const response = await axios[method](url, payload);

        // Usar el mensaje dinámico del backend para el toast
        displayToast("success", response.data.message);

        closeAllModals();
        router.reload({ only: ["medicalConsultations"] });
    } catch (e) {
        if (e.response?.status === 422) {
            formErrors.value = e.response.data.errors;
            const errorMessage =
                e.response.data.message || "Por favor, corrige los errores.";
            displayToast("danger", errorMessage);
        } else {
            const defaultMessage = isEditing
                ? "Error al actualizar la consulta."
                : "Error al crear la consulta.";
            displayToast("danger", e.response?.data?.message || defaultMessage);
        }
    } finally {
        loading.value = false;
    }
}

// MEJORA: Usar el mensaje dinámico del backend para el toast.
async function submitDelete() {
    const consultaId = resolveConsultaId(selectedConsultation.value);
    if (!consultaId) return;
    loading.value = true;
    try {
        const response = await axios.delete(urlEliminarConsulta(consultaId));

        displayToast("success", response.data.message);

        closeAllModals();
        router.reload({ only: ["medicalConsultations"] });
    } catch (e) {
        const errorMessage =
            e.response?.data?.message || "Error al eliminar la consulta.";
        displayToast("danger", errorMessage);
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <AdminLayout title="Consultas Médicas">
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType" closable>{{
                toastMsg
            }}</FwbToast>
        </div>

        <div class="flex justify-between my-6 items-center">
            <h2 class="text-2xl font-semibold vet-page-title">Consultas Médicas</h2>
            <div class="flex items-center space-x-2">
                <FwbButton
                    tag="a"
                    :href="route('consultas-medicas.report', filters)"
                    target="_blank"
                    color="green"
                >
                    <i class="fa-solid fa-file-pdf mr-2"></i> Reporte PDF
                </FwbButton>
                <FwbButton
                    v-if="canCreateMedCons"
                    @click="openCreateModal"
                    color="green"
                >
                    <i class="fa-solid fa-plus mr-2"></i> Agregar Consulta
                </FwbButton>
            </div>
        </div>

        <form
            @submit.prevent="applyFilters"
            class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6 vet-filter-panel"
        >
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700"
                    >Buscar por Mascota, Dueño o Motivo</label
                >
                <TextInput
                    v-model="filters.search_term"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="Ej: Max, Smith, vacuna..."
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700"
                    >Desde</label
                >
                <TextInput
                    v-model="filters.date_from"
                    type="date"
                    class="mt-1 block w-full"
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700"
                    >Hasta</label
                >
                <TextInput
                    v-model="filters.date_to"
                    type="date"
                    class="mt-1 block w-full"
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Estado</label>
                <select v-model="filters.estado" class="mt-1 block w-full border rounded px-2 py-2 dark:bg-gray-700 dark:border-gray-600">
                    <option value="">Todos</option>
                    <option v-for="(label, key) in estadosConsulta" :key="key" :value="key">{{ label }}</option>
                </select>
            </div>
            <div class="flex items-end space-x-2 lg:col-span-2">
                <FwbButton color="green" type="submit">Filtrar</FwbButton>
                <FwbButton color="alternative" @click.prevent="resetFilters"
                    >Limpiar</FwbButton
                >
            </div>
        </form>

        <div class="vet-table-scroll">
        <FwbTable class="vet-list-table">
            <FwbTableHead>
                <FwbTableHeadCell class="w-12">ID</FwbTableHeadCell>
                <FwbTableHeadCell class="whitespace-nowrap">Fecha</FwbTableHeadCell>
                <FwbTableHeadCell>Propietario</FwbTableHeadCell>
                <FwbTableHeadCell>Mascota</FwbTableHeadCell>
                <FwbTableHeadCell>Servicio</FwbTableHeadCell>
                <FwbTableHeadCell>Estado</FwbTableHeadCell>
                <FwbTableHeadCell>Motivo</FwbTableHeadCell>
                <FwbTableHeadCell class="vet-consultas-acciones">Acciones</FwbTableHeadCell>
            </FwbTableHead>
            <FwbTableBody>
                <FwbTableRow v-if="isEmptyData">
                    <FwbTableCell
                        colspan="8"
                        class="text-center text-gray-500 py-4"
                        >No se encontraron consultas con los filtros
                        aplicados.</FwbTableCell
                    >
                </FwbTableRow>
                <FwbTableRow
                    v-for="consultation in medicalConsultations.data"
                    :key="consultation.id"
                >
                    <FwbTableCell>{{ consultation.id }}</FwbTableCell>
                    <FwbTableCell>
                        {{ consultation.fecha || consultation.created_at }}
                        <span v-if="consultation.hora" class="text-gray-500 text-xs"> {{ consultation.hora }}</span>
                    </FwbTableCell>
                    <FwbTableCell class="max-w-[9rem] truncate" :title="consultation.pet_owner">{{ consultation.pet_owner }}</FwbTableCell>
                    <FwbTableCell class="max-w-[7rem] truncate" :title="consultation.pet_name">{{ consultation.pet_name }}</FwbTableCell>
                    <FwbTableCell class="max-w-[8rem] truncate" :title="consultation.servicio?.nombre || ''">{{ consultation.servicio?.nombre || "—" }}</FwbTableCell>
                    <FwbTableCell>
                        <FwbBadge :type="estadoBadge[consultation.estado] || 'dark'">
                            {{ estadosConsulta[consultation.estado] || consultation.estado || "—" }}
                        </FwbBadge>
                    </FwbTableCell>
                    <FwbTableCell class="max-w-xs truncate" :title="consultation.reason">{{
                        consultation.reason
                    }}</FwbTableCell>
                    <FwbTableCell class="vet-consultas-acciones">
                        <template v-if="canEditMedCons && transicionesRapidas[consultation.estado]">
                            <FwbButton
                                v-for="t in transicionesRapidas[consultation.estado]"
                                :key="t.estado"
                                type="button"
                                size="xs"
                                :color="t.color"
                                class="mr-1"
                                @click="solicitarCambiarEstado(consultation, t.estado)"
                            >{{ t.label }}</FwbButton>
                        </template>
                        <FwbButton
                            v-if="canManagePagos && saldoConsulta(consultation) > 0"
                            type="button"
                            size="xs"
                            color="green"
                            class="mr-1"
                            :title="`Saldo pendiente: Bs. ${saldoConsulta(consultation).toFixed(2)}`"
                            @click="abrirPagoModal(consultation)"
                        >
                            Cobrar
                        </FwbButton>
                        <a
                            :href="route('mascotas.historial', consultation.pet_id)"
                            class="vet-action-btn vet-action-btn--view mr-1"
                            title="Historial clínico"
                        >
                            <i class="fa-solid fa-clock-rotate-left fa-lg"></i>
                        </a>
                        <a
                            :href="
                                route(
                                    'consultas-medicas.historial-reporte',
                                    { pet: consultation.pet_id }
                                )
                            "
                            target="_blank"
                            class="vet-action-btn vet-action-btn--delete mr-1"
                            title="Generar historial clínico PDF"
                        >
                            <i class="fa-solid fa-file-pdf fa-lg"></i>
                        </a>
                        <TableActionButtons
                            :can-view="true"
                            :can-edit="canEditMedCons"
                            :can-delete="canDeleteMedCons"
                            view-title="Ver detalles"
                            @view="openViewModal(consultation)"
                            @edit="openEditModal(consultation)"
                            @delete="openDeleteModal(consultation)"
                        />
                    </FwbTableCell>
                </FwbTableRow>
            </FwbTableBody>
        </FwbTable>
        </div>

        <div v-if="!isEmptyData" class="flex justify-center my-4">
            <FwbPagination
                v-model="currentPage"
                :total-items="medicalConsultations.total"
                :per-page="medicalConsultations.per_page"
                large
            />
        </div>

        <FwbModal size="5xl" v-if="isCreateOrEditModal" @close="closeAllModals">
            <template #header>
                <h3 class="text-xl font-semibold">
                    {{ modalMode === "edit" ? "Editar" : "Nueva" }} Consulta
                    Médica
                </h3>
            </template>
            <template #body>
                <form class="space-y-6" @submit.prevent="submitForm">
                    <div class="mb-4 flex justify-between items-start">
                        <h3 class="text-xl font-semibold text-gray-700">
                            Datos de la mascota
                        </h3>
                        <FwbButton
                            @click="isSearchPetModal = true"
                            type="button"
                            color="green"
                            >Seleccionar Mascota</FwbButton
                        >
                    </div>
                    <div>
                        <div
                            v-if="!selectedPet"
                            class="border py-8 text-center text-gray-500 rounded-lg"
                            :class="{
                                'bg-red-50 border-red-300': formErrors.pet_id,
                            }"
                        >
                            Debe seleccionar una mascota
                            <InputError
                                class="mt-2"
                                :message="formErrors.pet_id?.[0]"
                            />
                        </div>
                        <div
                            v-else
                            class="grid grid-cols-1 md:grid-cols-2 gap-4 rounded bg-gray-50 border p-4"
                        >
                            <div>
                                <strong class="font-medium"
                                    >Propietario:</strong
                                >
                                <span>{{ selectedPet.owner_full_name }}</span>
                            </div>
                            <div>
                                <strong class="font-medium">Cédula:</strong>
                                <span>{{ selectedPet.owner?.ci }}</span>
                            </div>
                            <div>
                                <strong class="font-medium">Mascota:</strong>
                                <span>{{ selectedPet.name }}</span>
                            </div>
                            <div>
                                <strong class="font-medium"
                                    >Especie y Raza:</strong
                                >
                                <span>{{ selectedPet.specie_and_breed }}</span>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="resumenClinico && modalMode === 'create'"
                        class="rounded-lg border border-emerald-200 bg-emerald-50/80 p-4 text-sm space-y-2"
                    >
                        <div class="flex flex-wrap justify-between gap-2 items-center">
                            <strong>
                                {{
                                    resumenClinico.es_paciente_nuevo
                                        ? "Primera consulta — formulario completo"
                                        : "Paciente conocido — seguimiento"
                                }}
                            </strong>
                            <select
                                v-if="!resumenClinico.es_paciente_nuevo"
                                v-model="modoConsulta"
                                class="text-sm border rounded px-2 py-1"
                            >
                                <option value="seguimiento">Consulta de seguimiento</option>
                                <option value="inicial">Revisión completa</option>
                            </select>
                        </div>
                        <p v-if="resumenClinico.ultima_consulta">
                            Última visita: {{ resumenClinico.ultima_consulta.fecha }} —
                            {{ resumenClinico.ultima_consulta.motivo }}
                        </p>
                        <p v-if="resumenClinico.vacunas_pendientes">
                            Alertas sanitarias: {{ resumenClinico.vacunas_pendientes }}
                        </p>
                    </div>

                    <FormSectionTitle title="Anamnesis" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <FwbTextarea
                                v-model="form.reason"
                                label="Motivo de la consulta"
                                :rows="3"
                            /><InputError
                                class="mt-2"
                                :message="formErrors.reason?.[0]"
                            />
                        </div>
                        <template v-if="!esModoSeguimiento">
                        <div>
                            <InputLabel
                                value="Fecha de desparasitación"
                            /><TextInput
                                v-model="form.dewormed_at"
                                type="date"
                                class="mt-1 block w-full"
                            /><InputError
                                class="mt-2"
                                :message="formErrors.dewormed_at?.[0]"
                            />
                        </div>
                        <div>
                            <InputLabel
                                value="Enfermedades previas"
                            /><TextInput
                                v-model="form.previous_illnesses"
                                type="text"
                                class="mt-1 block w-full"
                            /><InputError
                                class="mt-2"
                                :message="formErrors.previous_illnesses?.[0]"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <InputLabel
                                value="Intervenciones previas"
                            /><TextInput
                                v-model="form.previous_interventions"
                                type="text"
                                class="mt-1 block w-full"
                            /><InputError
                                class="mt-2"
                                :message="
                                    formErrors.previous_interventions?.[0]
                                "
                            />
                        </div>
                        </template>
                    </div>
                    <FormSectionTitle title="Examen Físico" />
                    <div class="space-y-4">
                        <div
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6"
                        >
                            <div>
                                <InputLabel value="Estado General" />
                                <div class="estado-general-radio-group">
                                    <FwbRadio
                                        v-model="form.general_condition"
                                        value="Bueno"
                                        label="Bueno"
                                    /><FwbRadio
                                        v-model="form.general_condition"
                                        value="Regular"
                                        label="Regular"
                                    /><FwbRadio
                                        v-model="form.general_condition"
                                        value="Malo"
                                        label="Malo"
                                    />
                                </div>
                                <InputError
                                    :message="formErrors.general_condition?.[0]"
                                />
                            </div>
                            <template v-if="!esModoSeguimiento">
                            <div>
                                <InputLabel value="Apetito" />
                                <div
                                    class="flex flex-wrap gap-x-4 gap-y-2 mt-2"
                                >
                                    <label class="flex items-center"
                                        ><input
                                            type="checkbox"
                                            v-model="form.appetite"
                                            value="Normal"
                                            class="mr-2 rounded"
                                        />Normal</label
                                    ><label class="flex items-center"
                                        ><input
                                            type="checkbox"
                                            v-model="form.appetite"
                                            value="Disminuido"
                                            class="mr-2 rounded"
                                        />Disminuido</label
                                    ><label class="flex items-center"
                                        ><input
                                            type="checkbox"
                                            v-model="form.appetite"
                                            value="Anorexia"
                                            class="mr-2 rounded"
                                        />Anorexia</label
                                    >
                                </div>
                                <InputError
                                    :message="formErrors.appetite?.[0]"
                                />
                            </div>
                            <div>
                                <InputLabel value="Hidratación" />
                                <div
                                    class="flex flex-wrap gap-x-4 gap-y-2 mt-2"
                                >
                                    <label class="flex items-center"
                                        ><input
                                            type="checkbox"
                                            v-model="form.hydratation"
                                            value="Normal"
                                            class="mr-2 rounded"
                                        />Normal</label
                                    ><label class="flex items-center"
                                        ><input
                                            type="checkbox"
                                            v-model="form.hydratation"
                                            value="Leve"
                                            class="mr-2 rounded"
                                        />Leve</label
                                    ><label class="flex items-center"
                                        ><input
                                            type="checkbox"
                                            v-model="form.hydratation"
                                            value="Moderada"
                                            class="mr-2 rounded"
                                        />Moderada</label
                                    >
                                </div>
                                <InputError
                                    :message="formErrors.hydratation?.[0]"
                                />
                            </div>
                            <div>
                                <InputLabel value="Mucosa" />
                                <div
                                    class="flex flex-wrap gap-x-4 gap-y-2 mt-2"
                                >
                                    <label class="flex items-center"
                                        ><input
                                            type="checkbox"
                                            v-model="form.mucosa"
                                            value="Rosada"
                                            class="mr-2 rounded"
                                        />Rosada</label
                                    ><label class="flex items-center"
                                        ><input
                                            type="checkbox"
                                            v-model="form.mucosa"
                                            value="Pálida"
                                            class="mr-2 rounded"
                                        />Pálida</label
                                    ><label class="flex items-center"
                                        ><input
                                            type="checkbox"
                                            v-model="form.mucosa"
                                            value="Ictérica"
                                            class="mr-2 rounded"
                                        />Ictérica</label
                                    >
                                </div>
                                <InputError :message="formErrors.mucosa?.[0]" />
                            </div>
                            </template>
                        </div>
                        <div
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"
                        >
                            <div>
                                <InputLabel value="Peso (Kg)" /><TextInput
                                    v-model="form.weight"
                                    type="number"
                                    step="0.01"
                                    class="mt-1 w-full"
                                /><InputError
                                    :message="formErrors.weight?.[0]"
                                />
                            </div>
                            <template v-if="!esModoSeguimiento">
                            <div>
                                <InputLabel
                                    value="Temperatura (°C)"
                                /><TextInput
                                    v-model="form.temperature"
                                    type="number"
                                    step="0.1"
                                    class="mt-1 w-full"
                                /><InputError
                                    :message="formErrors.temperature?.[0]"
                                />
                            </div>
                            <div>
                                <InputLabel value="Frec. Cardíaca" /><TextInput
                                    v-model="form.heart_rate"
                                    type="number"
                                    class="mt-1 w-full"
                                /><InputError
                                    :message="formErrors.heart_rate?.[0]"
                                />
                            </div>
                            <div>
                                <InputLabel
                                    value="Frec. Respiratoria"
                                /><TextInput
                                    v-model="form.respiratory_rate"
                                    type="number"
                                    class="mt-1 w-full"
                                /><InputError
                                    :message="formErrors.respiratory_rate?.[0]"
                                />
                            </div>
                            </template>
                        </div>
                        <div
                            v-if="!esModoSeguimiento"
                            class="grid grid-cols-1 md:grid-cols-3 gap-4"
                        >
                            <div>
                                <InputLabel value="Ap. Digestivo" /><TextInput
                                    v-model="form.digestive_system"
                                    class="mt-1 w-full"
                                /><InputError
                                    :message="formErrors.digestive_system?.[0]"
                                />
                            </div>
                            <div>
                                <InputLabel
                                    value="Ap. Genitourinario"
                                /><TextInput
                                    v-model="form.genitourinary_system"
                                    class="mt-1 w-full"
                                /><InputError
                                    :message="
                                        formErrors.genitourinary_system?.[0]
                                    "
                                />
                            </div>
                            <div>
                                <InputLabel
                                    value="Ap. Respiratorio"
                                /><TextInput
                                    v-model="form.respiratory_system"
                                    class="mt-1 w-full"
                                /><InputError
                                    :message="
                                        formErrors.respiratory_system?.[0]
                                    "
                                />
                            </div>
                        </div>
                    </div>
                    <FormSectionTitle title="Diagnóstico y Tratamiento" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel
                                value="Diagnóstico Presuntivo"
                            /><TextInput
                                v-model="form.presumptive_diagnosis"
                                class="mt-1 w-full"
                            /><InputError
                                :message="formErrors.presumptive_diagnosis?.[0]"
                            />
                        </div>
                        <div>
                            <InputLabel
                                value="Diagnóstico Confirmativo"
                            /><TextInput
                                v-model="form.confirmatory_diagnosis"
                                class="mt-1 w-full"
                            /><InputError
                                :message="
                                    formErrors.confirmatory_diagnosis?.[0]
                                "
                            />
                        </div>
                        <div class="md:col-span-2">
                            <FwbTextarea
                                v-model="form.treatment"
                                label="Tratamiento y Evolución"
                                :rows="3"
                            /><InputError
                                :message="formErrors.treatment?.[0]"
                            />
                        </div>
                        <div>
                            <InputLabel value="Estado" />
                            <select
                                v-model="form.estado"
                                class="mt-1 w-full border rounded px-2 py-2 dark:bg-gray-700 dark:border-gray-600"
                            >
                                <option v-for="(label, key) in estadosConsulta" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Servicio" />
                            <select
                                v-model="form.service_id"
                                class="mt-1 w-full border rounded px-2 py-2 dark:bg-gray-700 dark:border-gray-600"
                            >
                                <option value="">— Seleccionar —</option>
                                <option v-for="s in servicios" :key="s.id" :value="s.id">
                                    {{ s.nombre }} (Bs. {{ s.precio }})
                                </option>
                            </select>
                            <InputError :message="formErrors.service_id?.[0] || formErrors.servicio_id?.[0]" />
                        </div>
                        <div>
                            <InputLabel
                                value="Costo Consulta (Bs.)"
                            /><TextInput
                                v-model="form.consultation_fee"
                                type="number"
                                step="0.01"
                                class="mt-1 w-full"
                            /><InputError
                                :message="formErrors.consultation_fee?.[0]"
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

        <FwbModal
            size="4xl"
            v-if="isViewModal && selectedConsultation"
            @close="closeAllModals"
        >
            <template #header
                ><h3 class="text-xl font-semibold">
                    Detalles de la Consulta Médica #{{
                        selectedConsultation.id
                    }}
                </h3></template
            >
            <template #body>
                <div class="space-y-6">
                    <section>
                        <FormSectionTitle title="Datos de la Mascota" />
                        <div
                            class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-2 text-sm mt-2 p-4 bg-gray-50 rounded-lg"
                        >
                            <p>
                                <strong class="font-semibold"
                                    >Propietario:</strong
                                >
                                {{ selectedConsultation.pet_owner }}
                            </p>
                            <p>
                                <strong class="font-semibold">Mascota:</strong>
                                {{ selectedConsultation.pet_name }}
                            </p>
                            <p v-if="selectedConsultation.pet?.owner?.ci">
                                <strong class="font-semibold">Cédula:</strong>
                                {{ selectedConsultation.pet.owner.ci }}
                            </p>
                            <p v-if="selectedConsultation.pet?.breed">
                                <strong class="font-semibold">Raza:</strong>
                                {{ selectedConsultation.pet.breed.name }}
                            </p>
                        </div>
                    </section>
                    <section>
                        <FormSectionTitle title="Anamnesis" />
                        <div class="space-y-2 text-sm mt-2">
                            <p>
                                <strong class="font-semibold">Motivo:</strong>
                                {{ selectedConsultation.reason || "N/A" }}
                            </p>
                            <p>
                                <strong class="font-semibold"
                                    >Fecha Desparasitación:</strong
                                >
                                {{ selectedConsultation.dewormed_at || "N/A" }}
                            </p>
                            <p>
                                <strong class="font-semibold"
                                    >Enfermedades Previas:</strong
                                >
                                {{
                                    selectedConsultation.previous_illnesses ||
                                    "N/A"
                                }}
                            </p>
                            <p>
                                <strong class="font-semibold"
                                    >Intervenciones Previas:</strong
                                >
                                {{
                                    selectedConsultation.previous_interventions ||
                                    "N/A"
                                }}
                            </p>
                        </div>
                    </section>
                    <section>
                        <FormSectionTitle title="Examen Físico" />
                        <div
                            class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-2 text-sm mt-2"
                        >
                            <p>
                                <strong class="font-semibold"
                                    >Estado General:</strong
                                >
                                {{
                                    selectedConsultation.general_condition ||
                                    "N/A"
                                }}
                            </p>
                            <p>
                                <strong class="font-semibold">Apetito:</strong>
                                {{ selectedConsultation.appetite || "N/A" }}
                            </p>
                            <p>
                                <strong class="font-semibold"
                                    >Hidratación:</strong
                                >
                                {{ selectedConsultation.hydratation || "N/A" }}
                            </p>
                            <p>
                                <strong class="font-semibold">Mucosa:</strong>
                                {{ selectedConsultation.mucosa || "N/A" }}
                            </p>
                            <p>
                                <strong class="font-semibold">Peso:</strong>
                                {{
                                    selectedConsultation.weight
                                        ? `${selectedConsultation.weight} Kg`
                                        : "N/A"
                                }}
                            </p>
                            <p>
                                <strong class="font-semibold"
                                    >Temperatura:</strong
                                >
                                {{
                                    selectedConsultation.temperature
                                        ? `${selectedConsultation.temperature} °C`
                                        : "N/A"
                                }}
                            </p>
                            <p>
                                <strong class="font-semibold"
                                    >Frec. Cardíaca:</strong
                                >
                                {{ selectedConsultation.heart_rate || "N/A" }}
                            </p>
                            <p>
                                <strong class="font-semibold"
                                    >Frec. Respiratoria:</strong
                                >
                                {{
                                    selectedConsultation.respiratory_rate ||
                                    "N/A"
                                }}
                            </p>
                            <p>
                                <strong class="font-semibold"
                                    >Ap. Digestivo:</strong
                                >
                                {{
                                    selectedConsultation.digestive_system ||
                                    "N/A"
                                }}
                            </p>
                            <p>
                                <strong class="font-semibold"
                                    >Ap. Genitourinario:</strong
                                >
                                {{
                                    selectedConsultation.genitourinary_system ||
                                    "N/A"
                                }}
                            </p>
                            <p>
                                <strong class="font-semibold"
                                    >Ap. Respiratorio:</strong
                                >
                                {{
                                    selectedConsultation.respiratory_system ||
                                    "N/A"
                                }}
                            </p>
                        </div>
                    </section>
                    <section>
                        <FormSectionTitle title="Diagnóstico y Tratamiento" />
                        <div class="space-y-2 text-sm mt-2">
                            <p>
                                <strong class="font-semibold"
                                    >Diagnóstico Presuntivo:</strong
                                >
                                {{
                                    selectedConsultation.presumptive_diagnosis ||
                                    "N/A"
                                }}
                            </p>
                            <p>
                                <strong class="font-semibold"
                                    >Diagnóstico Confirmativo:</strong
                                >
                                {{
                                    selectedConsultation.confirmatory_diagnosis ||
                                    "N/A"
                                }}
                            </p>
                            <div>
                                <strong class="font-semibold block"
                                    >Tratamiento y Evolución:</strong
                                >
                                <p
                                    class="mt-1 whitespace-pre-wrap bg-gray-50 p-2 rounded"
                                >
                                    {{
                                        selectedConsultation.treatment || "N/A"
                                    }}
                                </p>
                            </div>
                            <p>
                                <strong class="font-semibold"
                                    >Costo Consulta:</strong
                                >
                                {{
                                    selectedConsultation.consultation_fee
                                        ? `Bs. ${selectedConsultation.consultation_fee}`
                                        : "N/A"
                                }}
                            </p>
                        </div>
                    </section>
                </div>
            </template>
            <template #footer>
                <div class="flex justify-end">
                    <FwbButton @click="closeAllModals" color="alternative"
                        >Cerrar</FwbButton
                    >
                </div>
            </template>
        </FwbModal>

        <SearchModal
            v-if="isSearchPetModal"
            @close="isSearchPetModal = false"
            :search="search"
            @update:search="search = $event"
            :isFetchingData="isFetchingData"
            :results="petsList"
            @select="handleSelectPet"
            title="Seleccionar Mascota"
            placeholder="Buscar por nombre de mascota o propietario..."
        >
            <template #prefix
                ><div class="p-2"><SearchUser /></div
            ></template>
            <template #result="{ result }">
                <div v-if="result" class="w-full items-center flex">
                    <div class="mx-2">
                        <div class="font-semibold">{{ result.name }}</div>
                        <div class="text-xs text-gray-500">
                            Propietario: {{ result.owner?.first_name }}
                            {{ result.owner?.last_name }}
                        </div>
                    </div>
                </div>
            </template>
        </SearchModal>

        <FwbModal v-if="isEmergenciaModal" @close="cerrarEmergenciaModal">
            <template #header>Fuera de fecha de atención</template>
            <template #body>
                <p class="text-center text-lg">
                    Fuera de fecha de atención, vuelva.
                </p>
                <p
                    v-if="pendienteCambioEstado?.consultation?.fecha"
                    class="text-sm text-gray-600 mt-2 text-center"
                >
                    Fecha reservada:
                    <strong>{{ pendienteCambioEstado.consultation.fecha }}</strong>
                    <span v-if="pendienteCambioEstado.consultation.hora">
                        a las {{ pendienteCambioEstado.consultation.hora }}
                    </span>
                </p>
                <p class="text-sm text-gray-600 mt-2 text-center">
                    Si es una emergencia, puede registrar la atención de todas formas.
                </p>
            </template>
            <template #footer>
                <div class="flex justify-center w-full gap-2">
                    <FwbButton @click="cerrarEmergenciaModal" color="alternative">
                        Volver
                    </FwbButton>
                    <FwbButton
                        @click="confirmarAtencionEmergencia"
                        color="red"
                        :loading="loading"
                    >
                        Registrar emergencia
                    </FwbButton>
                </div>
            </template>
        </FwbModal>

        <FwbModal v-if="isPagoModal" @close="cerrarPagoModal">
            <template #header>Registrar pago — Consulta #{{ pagoConsulta?.id }}</template>
            <template #body>
                <p class="text-sm text-gray-600 mb-4">
                    Total: Bs. {{ Number(pagoConsulta?.costo_consulta ?? pagoConsulta?.consultation_fee ?? 0).toFixed(2) }}
                    · Pagado: Bs. {{ Number(pagoConsulta?.monto_pagado ?? 0).toFixed(2) }}
                    · <strong>Saldo: Bs. {{ saldoConsulta(pagoConsulta).toFixed(2) }}</strong>
                </p>
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Monto a cobrar (Bs.)" />
                        <TextInput v-model="pagoForm.monto" type="number" step="0.01" min="0.01" class="mt-1 w-full" />
                    </div>
                    <div>
                        <InputLabel value="Tipo de pago" />
                        <select v-model="pagoForm.tipo_pago" class="mt-1 w-full border rounded px-2 py-2">
                            <option v-for="(label, key) in tiposPago" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div v-if="pagoForm.tipo_pago === 'credito'">
                        <InputLabel value="Número de cuotas" />
                        <TextInput v-model="pagoForm.num_cuotas" type="number" min="2" max="12" class="mt-1 w-full" />
                    </div>
                    <div>
                        <InputLabel value="Método de pago" />
                        <select v-model="pagoForm.metodo_pago" class="mt-1 w-full border rounded px-2 py-2">
                            <option v-for="(label, key) in metodosPago" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div v-if="pagoForm.metodo_pago === 'qr'">
                        <div
                            v-if="pagoQrImage"
                            class="flex flex-col items-center bg-emerald-50 border border-emerald-100 rounded-lg p-4"
                        >
                            <img
                                :src="pagoQrImage"
                                alt="Código QR de pago"
                                class="max-w-[220px] rounded-lg"
                            />
                            <p v-if="pagoQrVerificando" class="text-sm text-emerald-700 mt-3">
                                Verificando pago QR...
                            </p>
                            <p v-else class="text-sm text-emerald-700 mt-3">
                                Escanee el QR para completar el pago.
                            </p>
                        </div>
                        <p v-else class="text-sm text-gray-600">
                            Al registrar, se generará el código QR por Bs. {{ Number(pagoForm.monto).toFixed(2) }}.
                        </p>
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="flex justify-end w-full gap-2">
                    <FwbButton @click="cerrarPagoModal" color="alternative">Cancelar</FwbButton>
                    <FwbButton
                        @click="registrarPagoConsulta"
                        color="green"
                        :loading="loading || pagoQrVerificando"
                        :disabled="pagoQrVerificando"
                    >
                        {{
                            pagoForm.metodo_pago === "qr" && !pagoQrImage
                                ? "Generar QR y cobrar"
                                : pagoForm.metodo_pago === "qr" && pagoQrVerificando
                                  ? "Verificando pago..."
                                  : "Registrar pago"
                        }}
                    </FwbButton>
                </div>
            </template>
        </FwbModal>

        <FwbModal v-if="isDeleteModal" @close="closeAllModals">
            <template #header>Confirmar Eliminación</template>
            <template #body>
                <p class="text-center text-lg">
                    ¿Estás seguro de que deseas eliminar la consulta #{{
                        selectedConsultation?.id
                    }}
                    para <strong>{{ selectedConsultation?.pet_name }}</strong
                    >?
                </p>
                <p class="text-sm text-gray-600 mt-2 text-center">
                    Esta acción no se puede deshacer.
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
    </AdminLayout>
</template>
