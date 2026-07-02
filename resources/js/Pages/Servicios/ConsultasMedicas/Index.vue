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
import { computed, ref, watch, inject, onMounted, onUnmounted } from "vue";
import axios from "axios";
import { usePermisos } from "@/Composables/usePermisos";
import { usePlanCredito } from "@/Composables/usePlanCredito";
import { usePagoQr, aplicarInfoPagoQrAForm } from "@/Composables/usePagoQr";
import PagoQrConfirmacionModal from "@/Components/Modals/PagoQrConfirmacionModal.vue";
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
    atenderConsulta: { type: Object, default: null },
    cobrarConsulta: { type: Object, default: null },
    minutosGracia: { type: Number, default: 20 },
    horaCierre: { type: String, default: "19:00" },
    horariosCita: { type: Array, default: () => [] },
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
    propietario: props.filters.propietario || "",
    mascota: props.filters.mascota || "",
    servicio_id: props.filters.servicio_id || "",
});

function applyFilters() {
    router.get(route("consultas-medicas.search"), filters.value, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    filters.value = {
        search_term: "", date_from: "", date_to: "", estado: "",
        propietario: "", mascota: "", servicio_id: "",
    };
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
const isRegistroLlegadaModal = ref(false);
const isReprogramarModal = ref(false);
const reprogramarModo = ref("no_asistio");
const reprogramarConsulta = ref(null);
const reprogramarForm = ref({ fecha: "", hora: "09:00" });
const reprogramarErrors = ref({});
const horariosDisponibles = computed(() => {
    if (props.horariosCita?.length) {
        return props.horariosCita;
    }
    const slots = [];
    for (let h = 9; h <= 17; h++) {
        if (h === 13) continue;
        for (const m of [0, 15, 30, 45]) {
            if (h === 17 && m > 0) break;
            slots.push(`${String(h).padStart(2, "0")}:${String(m).padStart(2, "0")}`);
        }
    }
    return slots;
});
const alertasCitasMostradas = new Set();
let alertaCitasInterval = null;
const ALERTA_CITA_MINUTOS = 30;
const registroLlegadaEmergencia = ref(false);
const registroConsulta = ref(null);
const registroForm = ref({
    first_name: "",
    last_name: "",
    ci: "",
    phone_number: "",
    email: "",
    gender: "0",
    address: "",
    pet_name: "",
    pet_color: "",
    pet_gender: "macho",
    pet_age: "",
    pet_weight: "",
});
const registroFormErrors = ref({});
const registroAllSpecies = ref([]);
const registroBreedsForSpecie = ref([]);
const registroSpecieMode = ref("list");
const registroBreedMode = ref("list");
const registroCustomSpecie = ref("");
const registroCustomBreed = ref("");
const registroSpecieSelect = ref("");
const registroBreedSelect = ref("");
const registroBreedId = ref("");
const isFetchingRegistroSpecies = ref(false);
const isFetchingRegistroBreeds = ref(false);
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

const {
    numPagos,
    pagosPlan,
    initPlan,
    rebuildPlan,
    sumaPlan,
    diferencia,
    planValido,
    maxFechaVencimiento,
    payloadCuotasPlan,
} = usePlanCredito(() => saldoConsulta(pagoConsulta.value));

const {
    pagoQrImage,
    pagoQrTransaccion,
    pagoQrNumeroPago,
    pagoQrVerificando,
    pagoQrInfo,
    showConfirmacionPago,
    limpiarQrPago,
    generarQrPago: generarQrApi,
    verificarQrPago,
    iniciarVerificacionQrPago,
    cerrarConfirmacionPago,
} = usePagoQr({
    onError: (msg) => {
        toastType.value = "danger";
        toastMsg.value = msg;
        showToast.value = true;
        setTimeout(() => (showToast.value = false), 3000);
    },
});

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

const esEstadoAgenda = computed(() =>
    ["reservada", "en_espera"].includes(form.value.estado)
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
    estado: "reservada",
    fecha: "",
    hora: "09:00",
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
    en_espera: "indigo",
    en_atencion: "blue",
    completada: "green",
    cancelada: "red",
    no_asistio: "purple",
};

const transicionesRapidas = {
    reservada: [
        { estado: "en_espera", label: "Check-in", color: "green", accion: "check-in" },
        { estado: "reprogramar_tarde", label: "Llegó tarde", color: "yellow", accion: "reprogramar-tarde" },
        { estado: "cancelada", label: "Cancelar", color: "red" },
        { estado: "no_asistio", label: "No asistió", color: "alternative", btnClass: "vet-btn-no-asistio" },
    ],
    en_espera: [
        { estado: "en_atencion", label: "Atender", color: "blue" },
        { estado: "cancelada", label: "Cancelar", color: "red" },
    ],
    en_atencion: [
        { estado: "completada", label: "Completar", color: "green" },
        { estado: "cancelada", label: "Cancelar", color: "red" },
    ],
};

const estadosAlCrear = ["reservada", "en_espera", "en_atencion", "completada"];

const estadosAlEditar = computed(() => {
    const actual = form.value.estado || "completada";
    const destinos = (transicionesRapidas[actual] || [])
        .map((t) => t.estado)
        .filter((e) => e !== "reprogramar_tarde");
    return [...new Set([actual, ...destinos])];
});

const estadosFormularioOpciones = computed(() => {
    const keys = modalMode.value === "create" ? estadosAlCrear : estadosAlEditar.value;
    return keys.map((key) => ({
        key,
        label: props.estadosConsulta[key] || key,
    }));
});



function esFechaPasada(consultation) {
    return esReservaVencida(consultation);
}

function normalizarHoraInput(hora) {
    if (!hora) return "09:00";
    const s = String(hora);
    return s.length >= 5 ? s.slice(0, 5) : s;
}

function parseInicioCita(consultation) {
    const fecha = normalizarFechaYmd(consultation.fecha);
    if (!fecha) return null;
    const hora = normalizarHoraInput(consultation.hora);
    const reserva = new Date(`${fecha}T${hora}:00`);
    return Number.isNaN(reserva.getTime()) ? null : reserva;
}

function verificarAlertasCitasProximas() {
    const lista = props.medicalConsultations?.data ?? [];
    const ahora = Date.now();

    for (const c of lista) {
        if (!["reservada", "en_espera", "en_atencion"].includes(c.estado)) continue;
        const inicio = parseInicioCita(c);
        if (!inicio) continue;

        const diff = inicio.getTime() - ahora;
        if (diff <= 0 || diff > ALERTA_CITA_MINUTOS * 60 * 1000) continue;

        const key = `cita-${c.id}-${c.estado}`;
        if (alertasCitasMostradas.has(key)) continue;

        alertasCitasMostradas.add(key);
        const minutos = Math.max(1, Math.ceil(diff / 60000));
        const esConsulta = c.estado === "reservada";
        toast(
            "warning",
            `Atención: ${esConsulta ? "consulta" : "revisión"} con ${c.pet_name || "paciente"} en ${minutos} min (${normalizarHoraInput(c.hora)}).`
        );
    }
}

function formatoFiltroFecha(valor) {
    if (!valor) return "";
    const partes = String(valor).slice(0, 10).split("-");
    if (partes.length !== 3) return valor;
    return `${partes[2]}/${partes[1]}/${partes[0].slice(-2)}`;
}

onMounted(() => {
    verificarAlertasCitasProximas();
    alertaCitasInterval = setInterval(verificarAlertasCitasProximas, 60000);
});

onUnmounted(() => {
    if (alertaCitasInterval) clearInterval(alertaCitasInterval);
});

function esReservaVencida(consultation) {
    const fecha = normalizarFechaYmd(consultation.fecha);
    if (!fecha) return false;

    const hora = consultation.hora
        ? String(consultation.hora).slice(0, 8)
        : "23:59:59";

    const reserva = new Date(`${fecha}T${hora}`);
    if (Number.isNaN(reserva.getTime())) return false;

    return reserva.getTime() < Date.now();
}

function transicionesParaConsulta(consultation) {
    const lista = transicionesRapidas[consultation.estado] || [];
    return lista.filter((t) => {
        if (t.estado === "no_asistio") {
            return consultation.puede_marcar_no_asistio;
        }
        if (t.accion === "reprogramar-tarde") {
            return consultation.puede_reprogramar_tarde;
        }
        return true;
    });
}

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

function requiereRegistroLlegada(consultation) {
    return Boolean(consultation?.requiere_registro_llegada);
}

function solicitarAccionEstado(consultation, transicion) {
    if (transicion.accion === "check-in") {
        solicitarCheckIn(consultation);
        return;
    }
    if (transicion.accion === "reprogramar-tarde") {
        abrirReprogramarModal(consultation, "tarde");
        return;
    }
    if (transicion.accion === "reprogramar") {
        abrirReprogramarModal(consultation, "no_asistio");
        return;
    }
    solicitarCambiarEstado(consultation, transicion.estado);
}

function solicitarCheckIn(consultation) {
    if (requiereRegistroLlegada(consultation)) {
        abrirRegistroLlegadaModal(consultation, false);
        return;
    }
    if (!esFechaAtencionHoy(consultation)) {
        pendienteCambioEstado.value = { consultation, nuevoEstado: "en_espera", accion: "check-in" };
        isEmergenciaModal.value = true;
        return;
    }
    ejecutarCheckIn(consultation, false);
}

async function ejecutarCheckIn(consultation, emergencia = false) {
    const consultaId = resolveConsultaId(consultation);
    if (!consultaId) return;
    loading.value = true;
    try {
        const { data } = await axios.post(
            route("consultas-medicas.check-in", { id: consultaId }),
            { emergencia }
        );
        displayToast("success", data.message);
        router.reload({ only: ["medicalConsultations"] });
    } catch (e) {
        displayToast("danger", e.response?.data?.message || "No se pudo registrar la llegada.");
    } finally {
        loading.value = false;
    }
}

function abrirReprogramarModal(consultation, modo = "no_asistio") {
    reprogramarConsulta.value = consultation;
    reprogramarModo.value = modo;
    reprogramarForm.value = { fecha: "", hora: horariosDisponibles.value[0] || "09:00" };
    reprogramarErrors.value = {};
    isReprogramarModal.value = true;
}

async function submitReprogramar() {
    const consultaId = resolveConsultaId(reprogramarConsulta.value);
    if (!consultaId) return;
    loading.value = true;
    reprogramarErrors.value = {};
    const url = reprogramarModo.value === "tarde"
        ? route("consultas-medicas.reprogramar-tarde", { id: consultaId })
        : route("consultas-medicas.reprogramar", { id: consultaId });
    try {
        const { data } = await axios.post(url, reprogramarForm.value);
        displayToast("success", data.message);
        isReprogramarModal.value = false;
        reprogramarConsulta.value = null;
        router.reload({ only: ["medicalConsultations"] });
    } catch (e) {
        if (e.response?.status === 422) {
            reprogramarErrors.value = e.response.data.errors || {};
        }
        displayToast("danger", e.response?.data?.message || "No se pudo reprogramar.");
    } finally {
        loading.value = false;
    }
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
    const { consultation, nuevoEstado, accion } = pendienteCambioEstado.value;
    cerrarEmergenciaModal();
    if (accion === "check-in") {
        if (requiereRegistroLlegada(consultation)) {
            abrirRegistroLlegadaModal(consultation, true);
            return;
        }
        ejecutarCheckIn(consultation, true);
        return;
    }
    cambiarEstado(consultation, nuevoEstado, true);
}

function resolveConsultaId(consultation) {
    const raw =
        consultation?.id ??
        consultation?.consulta_id ??
        editingConsultaId.value ??
        selectedConsultation.value?.id;
    const id = Number(raw);
    return Number.isFinite(id) && id > 0 ? id : null;
}

function urlCambiarEstadoConsulta(id) {
    const consultaId = Number(id);
    if (!Number.isFinite(consultaId) || consultaId <= 0) {
        throw new Error(`ID de consulta inválido: ${id}`);
    }
    return route("consultas-medicas.cambiar-estado", { id: consultaId });
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
        const url = urlCambiarEstadoConsulta(consultaId);
        const { data } = await axios.post(url, {
            estado: nuevoEstado,
            emergencia,
        });
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

function resetRegistroLlegadaState() {
    registroSpecieMode.value = "list";
    registroBreedMode.value = "list";
    registroCustomSpecie.value = "";
    registroCustomBreed.value = "";
    registroSpecieSelect.value = "";
    registroBreedSelect.value = "";
    registroBreedId.value = "";
    registroBreedsForSpecie.value = [];
    registroFormErrors.value = {};
}

async function loadRegistroSpecies() {
    isFetchingRegistroSpecies.value = true;
    try {
        const { data } = await axios.get(route("especies.search"), { params: { search: "" } });
        registroAllSpecies.value = data;
    } catch {
        registroAllSpecies.value = [];
        displayToast("danger", "No se pudieron cargar las especies.");
    } finally {
        isFetchingRegistroSpecies.value = false;
    }
}

async function fetchRegistroBreedsForSpecie(specieId) {
    if (!specieId) {
        registroBreedsForSpecie.value = [];
        return;
    }
    isFetchingRegistroBreeds.value = true;
    try {
        const { data } = await axios.get(route("razas.search"), {
            params: { search: "", specie_id: specieId },
        });
        registroBreedsForSpecie.value = data;
    } catch {
        registroBreedsForSpecie.value = [];
    } finally {
        isFetchingRegistroBreeds.value = false;
    }
}

function onRegistroSpecieChange() {
    if (registroSpecieSelect.value === "otro") {
        registroSpecieMode.value = "otro";
        registroBreedMode.value = "otro";
        registroBreedSelect.value = "otro";
        registroBreedId.value = "";
        registroBreedsForSpecie.value = [];
        return;
    }
    registroSpecieMode.value = "list";
    registroBreedMode.value = "list";
    registroBreedSelect.value = "";
    registroBreedId.value = "";
    registroCustomSpecie.value = "";
    registroCustomBreed.value = "";
    fetchRegistroBreedsForSpecie(registroSpecieSelect.value);
}

function onRegistroBreedChange() {
    if (registroBreedSelect.value === "otro") {
        registroBreedMode.value = "otro";
        registroBreedId.value = "";
        return;
    }
    registroBreedMode.value = "list";
    registroBreedId.value = registroBreedSelect.value;
    registroCustomBreed.value = "";
}

async function abrirRegistroLlegadaModal(consultation, emergencia = false) {
    const cliente =
        consultation?.mascota?.propietario ??
        consultation?.mascota?.cliente ??
        consultation?.mascota?.owner;
    const mascota = consultation?.mascota;

    resetRegistroLlegadaState();
    registroConsulta.value = consultation;
    registroLlegadaEmergencia.value = emergencia;
    registroForm.value = {
        first_name: cliente?.nombre || cliente?.first_name || "",
        last_name: cliente?.apellido || cliente?.last_name || "",
        ci: cliente?.ci && cliente.ci !== cliente?.telefono ? cliente.ci : "",
        phone_number: cliente?.telefono || cliente?.phone_number || "",
        email: cliente?.email || "",
        gender: String(cliente?.gender ?? cliente?.genero ?? "0"),
        address: cliente?.direccion || cliente?.address || "",
        pet_name: mascota?.nombre || mascota?.name || consultation?.pet_name || "",
        pet_color: mascota?.color || "",
        pet_gender: mascota?.genero === "hembra" || mascota?.genero === "0" ? "hembra" : "macho",
        pet_age: mascota?.age ?? "",
        pet_weight: mascota?.peso ?? mascota?.weight ?? "",
    };

    if (mascota?.raza_id) {
        registroBreedId.value = String(mascota.raza_id);
        const raza = mascota.raza;
        if (raza?.especie_id) {
            registroSpecieSelect.value = String(raza.especie_id);
            await fetchRegistroBreedsForSpecie(raza.especie_id);
            registroBreedSelect.value = String(mascota.raza_id);
        }
    }

    await loadRegistroSpecies();
    isRegistroLlegadaModal.value = true;
}

function cerrarRegistroLlegadaModal() {
    isRegistroLlegadaModal.value = false;
    registroConsulta.value = null;
    registroLlegadaEmergencia.value = false;
    resetRegistroLlegadaState();
}

async function submitRegistroLlegada() {
    const consultaId = resolveConsultaId(registroConsulta.value);
    if (!consultaId) {
        displayToast("danger", "No se pudo identificar la consulta.");
        return;
    }

    registroFormErrors.value = {};
    loading.value = true;

    const payload = {
        ...registroForm.value,
        emergencia: registroLlegadaEmergencia.value,
    };

    if (registroBreedMode.value === "list" && registroBreedId.value) {
        payload.breed_id = Number(registroBreedId.value);
    } else if (registroSpecieMode.value === "otro" || registroBreedMode.value === "otro") {
        payload.specie = registroCustomSpecie.value.trim();
        payload.breed = registroCustomBreed.value.trim();
    } else if (registroBreedSelect.value) {
        payload.breed_id = Number(registroBreedSelect.value);
    }

    try {
        const { data } = await axios.post(
            route("consultas-medicas.registro-llegada", { id: consultaId }),
            payload
        );
        displayToast("success", data.message);
        cerrarRegistroLlegadaModal();
        router.reload({ only: ["medicalConsultations"] });
    } catch (e) {
        if (e.response?.status === 422) {
            registroFormErrors.value = e.response.data.errors || {};
            displayToast("danger", e.response.data.message || "Revise los datos del formulario.");
        } else {
            displayToast("danger", e.response?.data?.message || "No se pudo completar el registro.");
        }
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
    pagosPlan.value = [];
    isPagoModal.value = true;
}

watch(() => pagoForm.value.tipo_pago, (tipo) => {
    if (tipo === "credito" && pagoConsulta.value) {
        initPlan();
    }
    if (tipo === "contado") {
        pagosPlan.value = [];
    }
});

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
    const ok = await generarQrApi({
        ...cliente,
        monto,
        serviceId: pagoConsulta.value?.servicio_id || pagoConsulta.value?.service_id,
    });
    if (ok) {
        pagoForm.value.id_transaccion_externa =
            pagoQrTransaccion.value || pagoQrNumeroPago.value || "";
    }
    return ok;
}

async function confirmarPagoQrYGuardarConsulta() {
    if (pagoQrInfo.value) {
        aplicarInfoPagoQrAForm(pagoForm.value, pagoQrInfo.value, pagosPlan.value);
    }
    cerrarConfirmacionPago();
    await guardarPagoConsulta();
}

async function guardarPagoConsulta() {
    if (!pagoConsulta.value?.id) return;
    if (pagoForm.value.tipo_pago === "credito" && pagosPlan.value.length && !planValido.value) {
        displayToast("danger", "La suma de los pagos debe igualar el saldo pendiente.");
        return;
    }
    loading.value = true;
    try {
        const payload = {
            consulta_id: pagoConsulta.value.id,
            monto: pagoForm.value.monto,
            tipo_pago: pagoForm.value.tipo_pago,
            metodo_pago: pagoForm.value.metodo_pago,
            concepto_pago: "saldo",
            id_transaccion_externa: pagoForm.value.id_transaccion_externa || null,
            fecha_pago: pagoForm.value.fecha_pago || null,
        };
        if (pagoForm.value.tipo_pago === "credito" && pagosPlan.value.length) {
            payload.cuotas_plan = payloadCuotasPlan();
            payload.monto = Number(pagosPlan.value[0]?.monto) || payload.monto;
        } else if (pagoForm.value.tipo_pago === "credito") {
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
            if (pagado) return;
            else {
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
    const now = new Date();
    const estado = data.estado || "completada";

    return {
        ...data,
        appetite: joinOrDefault(data.appetite),
        hydratation: joinOrDefault(data.hydratation),
        mucosa: joinOrDefault(data.mucosa),
        modo_consulta: esModoSeguimiento.value ? "seguimiento" : "inicial",
        veterinarian_id: page.props.auth.user.id,
        estado,
        fecha: data.fecha || fechaHoyYmd(),
        hora: data.hora || now.toTimeString().slice(0, 8),
    };
}

function openCreateModal() {
    modalMode.value = "create";
    editingConsultaId.value = null;
    const hoy = fechaHoyYmd();
    form.value = {
        ...defaultFormState,
        fecha: hoy,
        hora: horariosDisponibles.value[0] || "09:00",
    };
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

    if (modalMode.value === "create" && !editingConsultaId.value && form.value.estado === "en_atencion") {
        iniciarAtencionMascota(pet.id);
    }
}

async function iniciarAtencionMascota(petId) {
    try {
        const { data } = await axios.post(route("consultas-medicas.iniciar-atencion"), {
            pet_id: petId,
            service_id: form.value.service_id || null,
            reason: form.value.reason || null,
        });
        editingConsultaId.value = data.consultation.id;
        selectedConsultation.value = data.consultation;
        modalMode.value = "edit";
        form.value.id = data.consultation.id;
        displayToast("success", data.message);
    } catch (e) {
        displayToast(
            "danger",
            e.response?.data?.message || "No se pudo iniciar la atención en agenda."
        );
    }
}

onMounted(() => {
    if (props.atenderConsulta) {
        openEditModal(props.atenderConsulta);
    }
    if (props.cobrarConsulta) {
        abrirPagoModal(props.cobrarConsulta);
    }
});

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
            <div class="flex gap-2">
                <FwbA :href="route('consultas-medicas.agenda')" class="vet-btn-secondary inline-flex items-center px-4 py-2 rounded-lg text-sm">
                    <i class="fa-solid fa-calendar-day mr-2"></i>Agenda del día
                </FwbA>
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
        </div>

        <div class="vet-table-scroll">
        <FwbTable class="vet-list-table vet-consultas-table">
            <FwbTableHead>
                    <FwbTableHeadCell class="w-12 vet-th-cell">
                        <span class="vet-th-label">ID</span>
                    </FwbTableHeadCell>
                    <FwbTableHeadCell class="vet-th-cell vet-th-fecha">
                        <div class="vet-th-filter-stack">
                            <label class="vet-th-filter-row text-xs text-gray-500">
                                De:
                                <input v-model="filters.date_from" type="date" class="vet-th-filter" />
                            </label>
                            <label class="vet-th-filter-row text-xs text-gray-500">
                                Hasta:
                                <input v-model="filters.date_to" type="date" class="vet-th-filter" />
                            </label>
                            <p
                                v-if="filters.date_from || filters.date_to"
                                class="text-[10px] text-gray-500 leading-tight mt-0.5"
                            >
                                <span v-if="filters.date_from">De: {{ formatoFiltroFecha(filters.date_from) }}</span>
                                <span v-if="filters.date_from && filters.date_to"> · </span>
                                <span v-if="filters.date_to">Hasta: {{ formatoFiltroFecha(filters.date_to) }}</span>
                            </p>
                        </div>
                        <span class="vet-th-label">Fecha</span>
                    </FwbTableHeadCell>
                    <FwbTableHeadCell class="vet-th-cell">
                        <input
                            v-model="filters.propietario"
                            type="text"
                            class="vet-th-filter"
                            placeholder="Filtrar..."
                            @keyup.enter="applyFilters"
                        />
                        <span class="vet-th-label">Propietario</span>
                    </FwbTableHeadCell>
                    <FwbTableHeadCell class="vet-th-cell">
                        <input
                            v-model="filters.mascota"
                            type="text"
                            class="vet-th-filter"
                            placeholder="Filtrar..."
                            @keyup.enter="applyFilters"
                        />
                        <span class="vet-th-label">Mascota</span>
                    </FwbTableHeadCell>
                    <FwbTableHeadCell class="vet-th-cell">
                        <select v-model="filters.servicio_id" class="vet-th-filter">
                            <option value="">Todos</option>
                            <option v-for="s in servicios" :key="s.id" :value="s.id">{{ s.nombre }}</option>
                        </select>
                        <span class="vet-th-label">Servicio</span>
                    </FwbTableHeadCell>
                    <FwbTableHeadCell class="vet-th-cell">
                        <select v-model="filters.estado" class="vet-th-filter">
                            <option value="">Todos</option>
                            <option v-for="(label, key) in estadosConsulta" :key="key" :value="key">{{ label }}</option>
                        </select>
                        <span class="vet-th-label">Estado</span>
                    </FwbTableHeadCell>
                    <FwbTableHeadCell class="vet-th-cell">
                        <span class="vet-th-label">Motivo</span>
                    </FwbTableHeadCell>
                    <FwbTableHeadCell class="vet-th-cell vet-consultas-acciones">
                        <div class="vet-th-filter-actions">
                            <button type="button" class="vet-th-btn vet-th-btn--primary" @click="applyFilters">Filtrar</button>
                            <button type="button" class="vet-th-btn" @click="resetFilters">Limpiar</button>
                        </div>
                        <span class="vet-th-label">Acciones</span>
                    </FwbTableHeadCell>
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
                    <FwbTableCell class="w-12">{{ consultation.id }}</FwbTableCell>
                    <FwbTableCell class="vet-th-fecha">
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
                        <FwbBadge
                            v-if="consultation.requiere_registro_llegada"
                            type="warning"
                            class="ml-1"
                            title="Reserva web: completar registro al llegar"
                        >
                            Registro pendiente
                        </FwbBadge>
                    </FwbTableCell>
                    <FwbTableCell class="max-w-xs truncate" :title="consultation.reason">{{
                        consultation.reason
                    }}</FwbTableCell>
                    <FwbTableCell class="vet-consultas-acciones">
                        <div class="vet-acciones-wrap">
                        <template v-if="canEditMedCons && transicionesParaConsulta(consultation).length">
                            <FwbButton
                                v-for="t in transicionesParaConsulta(consultation)"
                                :key="t.estado"
                                type="button"
                                size="xs"
                                :color="t.color"
                                :class="['mr-1', t.btnClass].filter(Boolean)"
                                @click="solicitarAccionEstado(consultation, t)"
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
                        </div>
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
                <p
                    v-if="modalMode === 'create'"
                    class="text-sm text-sky-800 bg-sky-50 border border-sky-100 rounded-lg px-4 py-3 mb-4"
                >
                    {{ hintCrearConsulta }}
                </p>
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
                                <option
                                    v-for="opt in estadosFormularioOpciones"
                                    :key="opt.key"
                                    :value="opt.key"
                                >
                                    {{ opt.label }}
                                </option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ hintCrearConsulta }}
                            </p>
                            <p
                                v-if="accionesEnTablaHint"
                                class="text-xs text-sky-800 bg-sky-50 border border-sky-100 rounded-lg px-3 py-2 mt-2"
                            >
                                <strong>Después de guardar:</strong> {{ accionesEnTablaHint }}
                            </p>
                           
                        </div>
                        <div v-if="esEstadoAgenda">
                            <InputLabel value="Fecha de la cita *" />
                            <input
                                v-model="form.fecha"
                                type="date"
                                class="mt-1 w-full border rounded px-2 py-2 dark:bg-gray-700 dark:border-gray-600"
                            />
                            <InputError :message="formErrors.fecha?.[0]" />
                        </div>
                        <div v-if="esEstadoAgenda">
                            <InputLabel value="Hora de la cita *" />
                            <input
                                v-model="form.hora"
                                type="time"
                                step="60"
                                class="mt-1 w-full border rounded px-2 py-2 dark:bg-gray-700 dark:border-gray-600"
                            />
                            <p class="text-xs text-gray-500 mt-1">Formato 24 h (horas y minutos).</p>
                            <InputError :message="formErrors.hora?.[0]" />
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

        <FwbModal v-if="isRegistroLlegadaModal" @close="cerrarRegistroLlegadaModal" size="2xl">
            <template #header>
                Registro en llegada — Consulta #{{ registroConsulta?.id }}
            </template>
            <template #body>
                <p class="text-sm text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-4 py-3 mb-5">
                    Esta cita se reservó por la página web con datos mínimos.
                    Complete el registro del propietario y la mascota para iniciar la atención.
                </p>
                <div class="space-y-6">
                    <FormSectionTitle title="Propietario" />
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Nombre *" />
                            <TextInput v-model="registroForm.first_name" class="mt-1 w-full" />
                            <InputError :message="registroFormErrors.first_name?.[0]" />
                        </div>
                        <div>
                            <InputLabel value="Apellido *" />
                            <TextInput v-model="registroForm.last_name" class="mt-1 w-full" />
                            <InputError :message="registroFormErrors.last_name?.[0]" />
                        </div>
                        <div>
                            <InputLabel value="CI *" />
                            <TextInput v-model="registroForm.ci" class="mt-1 w-full" />
                            <InputError :message="registroFormErrors.ci?.[0]" />
                        </div>
                        <div>
                            <InputLabel value="Teléfono *" />
                            <TextInput v-model="registroForm.phone_number" class="mt-1 w-full" />
                            <InputError :message="registroFormErrors.phone_number?.[0]" />
                        </div>
                        <div>
                            <InputLabel value="Correo" />
                            <TextInput v-model="registroForm.email" type="email" class="mt-1 w-full" />
                            <InputError :message="registroFormErrors.email?.[0]" />
                        </div>
                        <div>
                            <InputLabel value="Género *" />
                            <div class="flex gap-4 mt-2">
                                <FwbRadio v-model="registroForm.gender" value="0" label="Masculino" />
                                <FwbRadio v-model="registroForm.gender" value="1" label="Femenino" />
                                <FwbRadio v-model="registroForm.gender" value="2" label="Otro" />
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <InputLabel value="Dirección" />
                            <TextInput v-model="registroForm.address" class="mt-1 w-full" />
                        </div>
                    </div>

                    <FormSectionTitle title="Mascota" />
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Nombre *" />
                            <TextInput v-model="registroForm.pet_name" class="mt-1 w-full" />
                            <InputError :message="registroFormErrors.pet_name?.[0]" />
                        </div>
                        <div>
                            <InputLabel value="Color *" />
                            <TextInput v-model="registroForm.pet_color" class="mt-1 w-full" />
                            <InputError :message="registroFormErrors.pet_color?.[0]" />
                        </div>
                        <div>
                            <InputLabel value="Especie *" />
                            <select
                                v-model="registroSpecieSelect"
                                class="mt-1 w-full border rounded px-2 py-2"
                                @change="onRegistroSpecieChange"
                            >
                                <option value="">Seleccione especie</option>
                                <option v-for="s in registroAllSpecies" :key="s.id" :value="s.id">
                                    {{ s.name || s.nombre }}
                                </option>
                                <option value="otro">Otra especie...</option>
                            </select>
                            <TextInput
                                v-if="registroSpecieMode === 'otro'"
                                v-model="registroCustomSpecie"
                                class="mt-2 w-full"
                                placeholder="Nombre de la especie"
                            />
                        </div>
                        <div>
                            <InputLabel value="Raza *" />
                            <select
                                v-if="registroBreedMode === 'list' && registroSpecieMode !== 'otro'"
                                v-model="registroBreedSelect"
                                class="mt-1 w-full border rounded px-2 py-2"
                                :disabled="!registroSpecieSelect || isFetchingRegistroBreeds"
                                @change="onRegistroBreedChange"
                            >
                                <option value="">Seleccione raza</option>
                                <option v-for="b in registroBreedsForSpecie" :key="b.id" :value="b.id">
                                    {{ b.name || b.nombre }}
                                </option>
                                <option value="otro">Otra raza...</option>
                            </select>
                            <TextInput
                                v-if="registroBreedMode === 'otro' || registroSpecieMode === 'otro'"
                                v-model="registroCustomBreed"
                                class="mt-1 w-full"
                                placeholder="Nombre de la raza"
                            />
                        </div>
                        <div>
                            <InputLabel value="Sexo" />
                            <select v-model="registroForm.pet_gender" class="mt-1 w-full border rounded px-2 py-2">
                                <option value="macho">Macho</option>
                                <option value="hembra">Hembra</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Edad (años)" />
                            <TextInput v-model="registroForm.pet_age" type="number" min="0" class="mt-1 w-full" />
                        </div>
                        <div>
                            <InputLabel value="Peso (kg)" />
                            <TextInput v-model="registroForm.pet_weight" type="number" step="0.01" min="0" class="mt-1 w-full" />
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="flex justify-end gap-2 w-full">
                    <FwbButton @click="cerrarRegistroLlegadaModal" color="alternative">
                        Cancelar
                    </FwbButton>
                    <FwbButton @click="submitRegistroLlegada" color="blue" :loading="loading">
                        Registrar y poner en espera
                    </FwbButton>
                </div>
            </template>
        </FwbModal>

        <FwbModal v-if="isReprogramarModal" @close="isReprogramarModal = false">
            <template #header>
                {{ reprogramarModo === 'tarde' ? 'Reprogramar por llegada tarde' : 'Reprogramar cita' }}
                #{{ reprogramarConsulta?.id }}
            </template>
            <template #body>
                <p
                    v-if="reprogramarModo === 'tarde'"
                    class="text-sm text-sky-800 bg-sky-50 border border-sky-100 rounded-lg px-4 py-3 mb-4"
                >
                    Única reprogramación permitida por llegada tarde. El anticipo pagado se conserva.
                </p>
                <p
                    v-else
                    class="text-sm text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-4 py-3 mb-4"
                >
                    El anticipo ya se perdió por no asistencia. La nueva cita requiere anticipo nuevamente.
                </p>
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Nueva fecha *" />
                        <input v-model="reprogramarForm.fecha" type="date" class="mt-1 w-full border rounded px-3 py-2" :min="fechaHoyYmd()" />
                        <InputError :message="reprogramarErrors.fecha?.[0]" />
                    </div>
                    <div>
                        <InputLabel value="Nuevo horario *" />
                        <select v-model="reprogramarForm.hora" class="mt-1 w-full border rounded px-3 py-2">
                            <option v-for="h in horariosDisponibles" :key="h" :value="h">{{ h }}</option>
                        </select>
                        <InputError :message="reprogramarErrors.hora?.[0]" />
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <FwbButton color="alternative" @click="isReprogramarModal = false">Cancelar</FwbButton>
                    <FwbButton color="yellow" :loading="loading" @click="submitReprogramar">Reprogramar</FwbButton>
                </div>
            </template>
        </FwbModal>

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
                    <div v-if="pagoForm.tipo_pago !== 'credito'">
                        <InputLabel value="Monto a cobrar (Bs.)" />
                        <TextInput v-model="pagoForm.monto" type="number" step="0.01" min="0.01" class="mt-1 w-full" />
                    </div>
                    <div>
                        <InputLabel value="Tipo de pago" />
                        <select v-model="pagoForm.tipo_pago" class="mt-1 w-full border rounded px-2 py-2">
                            <option v-for="(label, key) in tiposPago" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div v-if="pagoForm.tipo_pago === 'credito'" class="space-y-3">
                        <div>
                            <InputLabel value="Número de pagos (inicial + cuotas)" />
                            <select v-model="numPagos" class="mt-1 w-full border rounded px-2 py-2" @change="rebuildPlan">
                                <option v-for="n in 11" :key="n + 1" :value="n + 1">{{ n + 1 }} pagos</option>
                            </select>
                        </div>
                        <div class="border rounded-lg overflow-hidden text-sm">
                            <table class="w-full">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Pago</th>
                                        <th class="px-3 py-2 text-left">Monto</th>
                                        <th class="px-3 py-2 text-left">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(pago, idx) in pagosPlan" :key="idx" class="border-t">
                                        <td class="px-3 py-2">{{ pago.etiqueta }}</td>
                                        <td class="px-3 py-2">
                                            <TextInput v-model="pago.monto" type="number" step="0.01" class="w-full" />
                                        </td>
                                        <td class="px-3 py-2">
                                            <TextInput v-if="pago.esInicial" v-model="pago.fecha" type="datetime-local" class="w-full" />
                                            <TextInput v-else v-model="pago.fecha" type="date" class="w-full" :max="maxFechaVencimiento()" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-sm rounded-lg p-3 border" :class="planValido ? 'bg-green-50 border-green-200 text-green-800' : 'bg-amber-50 border-amber-200 text-amber-800'">
                            Suma: Bs. {{ sumaPlan.toFixed(2) }} · Saldo: Bs. {{ saldoConsulta(pagoConsulta).toFixed(2) }}
                            <span v-if="!planValido"> · Ajuste: Bs. {{ Math.abs(diferencia).toFixed(2) }}</span>
                        </p>
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

        <PagoQrConfirmacionModal
            :show="showConfirmacionPago"
            :info="pagoQrInfo"
            :loading="loading"
            @confirmar="confirmarPagoQrYGuardarConsulta"
        />

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
