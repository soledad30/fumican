<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { router, Link } from "@inertiajs/vue3";
import { FwbBadge, FwbButton, FwbModal, FwbToast, FwbRadio } from "flowbite-vue";
import { computed, inject, ref, onMounted, onUnmounted } from "vue";
import axios from "axios";
import { usePermisos } from "@/Composables/usePermisos";
import InputError from "@/Components/InputError.vue";
import { ahoraFechaYmd, esFechaHoyBolivia } from "@/Utils/fechaBolivia";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import FormSectionTitle from "@/Components/Forms/FormSectionTitle.vue";

const route = inject("route");

const props = defineProps({
    agenda: Object,
    recordatorios: Array,
    estadosConsulta: Object,
    horarios: Array,
    fecha: String,
    fechaRecordatorios: String,
    minutosGracia: Number,
    horaCierre: String,
});

const { puede } = usePermisos();
const canEdit = computed(() => puede("editar consultas medicas"));
const canManagePagos = computed(() => puede("gestionar pagos") || puede("crear pagos"));

const estadoBadge = {
    reservada: "yellow",
    en_espera: "indigo",
    en_atencion: "blue",
    completada: "green",
    cancelada: "red",
    no_asistio: "purple",
};

let refreshInterval = null;

const loading = ref(false);
const showToast = ref(false);
const toastMsg = ref("");
const toastType = ref("success");

const fechaAgenda = ref(props.fecha);
const fechaRecordatorio = ref(props.fechaRecordatorios);

const columnasKanban = [
    { key: "reservada", label: "Reservadas", icon: "fa-calendar", tone: "border-amber-200 bg-amber-50" },
    { key: "en_espera", label: "En espera", icon: "fa-chair", tone: "border-indigo-200 bg-indigo-50" },
    { key: "en_atencion", label: "En atención", icon: "fa-stethoscope", tone: "border-sky-200 bg-sky-50" },
    { key: "completada", label: "Completadas", icon: "fa-circle-check", tone: "border-emerald-200 bg-emerald-50" },
    { key: "no_asistio", label: "No asistió", icon: "fa-user-slash", tone: "border-purple-200 bg-purple-50" },
];

const isRegistroModal = ref(false);
const isReprogramarModal = ref(false);
const reprogramarModo = ref("tarde");
const isEmergenciaModal = ref(false);
const consultaActiva = ref(null);
const emergenciaAccion = ref(null);
const registroEmergencia = ref(false);

const registroForm = ref({
    first_name: "", last_name: "", ci: "", phone_number: "", email: "",
    gender: "0", address: "", pet_name: "", pet_color: "",
    pet_gender: "macho", pet_age: "", pet_weight: "",
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

const reprogramarForm = ref({ fecha: "", hora: "" });
const reprogramarErrors = ref({});

function displayToast(type, message) {
    toastType.value = type;
    toastMsg.value = message;
    showToast.value = true;
    setTimeout(() => (showToast.value = false), 3500);
}

function recargarAgenda() {
    router.get(route("consultas-medicas.agenda"), {
        fecha: fechaAgenda.value,
        fecha_recordatorios: fechaRecordatorio.value,
    }, { preserveState: true, replace: true });
}

function cambiarFechaAgenda() {
    recargarAgenda();
}

function cambiarFechaRecordatorios() {
    recargarAgenda();
}

function saldoConsulta(c) {
    const saldo = c.saldo_pendiente ?? c.saldoPendiente;
    if (saldo != null) return Number(saldo);
    const total = Number(c.costo_consulta ?? c.consultation_fee ?? 0);
    const pagado = Number(c.monto_pagado ?? 0);
    return Math.max(0, total - pagado);
}

function urlFichaClinica(id) {
    return route("consultas-medicas.index", { atender: id });
}

function urlCobrar(id) {
    return route("consultas-medicas.index", { cobrar: id });
}

function etiquetaEstado(estado) {
    return props.estadosConsulta[estado] || estado;
}

onMounted(() => {
    refreshInterval = setInterval(recargarAgenda, 45000);
});

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
});

function horaCita(c) {
    if (!c?.hora) return "—";
    return String(c.hora).slice(0, 5);
}

function telefonoCliente(c) {
    const cliente = c?.mascota?.propietario ?? c?.mascota?.cliente;
    return cliente?.telefono || cliente?.phone_number || "";
}

function mensajeRecordatorio(c) {
    const nombre = c.pet_owner || "estimado cliente";
    const mascota = c.pet_name || "su mascota";
    const hora = horaCita(c);
    const servicio = c.servicio?.nombre || "consulta veterinaria";
    return `Hola ${nombre}, le recordamos la cita de ${mascota} para ${servicio} el ${fechaRecordatorio.value} a las ${hora}. Por favor confirme su asistencia. — Fumican Vet`;
}

function enlaceWhatsApp(c) {
    const tel = telefonoCliente(c).replace(/\D/g, "");
    if (!tel) return null;
    const numero = tel.startsWith("591") ? tel : `591${tel.replace(/^0+/, "")}`;
    return `https://wa.me/${numero}?text=${encodeURIComponent(mensajeRecordatorio(c))}`;
}

async function cambiarEstado(consulta, estado, emergencia = false) {
    loading.value = true;
    try {
        const { data } = await axios.post(
            route("consultas-medicas.cambiar-estado", { id: consulta.id }),
            { estado, emergencia }
        );
        displayToast("success", data.message);
        recargarAgenda();
    } catch (e) {
        displayToast("danger", e.response?.data?.message || "No se pudo cambiar el estado.");
    } finally {
        loading.value = false;
    }
}

function solicitarCheckIn(consulta) {
    if (consulta.requiere_registro_llegada) {
        abrirRegistroModal(consulta, false);
        return;
    }
    if (!esFechaHoy(consulta.fecha)) {
        consultaActiva.value = consulta;
        emergenciaAccion.value = "check-in";
        isEmergenciaModal.value = true;
        return;
    }
    ejecutarCheckIn(consulta, false);
}

async function ejecutarCheckIn(consulta, emergencia) {
    loading.value = true;
    try {
        const { data } = await axios.post(
            route("consultas-medicas.check-in", { id: consulta.id }),
            { emergencia }
        );
        displayToast("success", data.message);
        recargarAgenda();
    } catch (e) {
        displayToast("danger", e.response?.data?.message || "No se pudo registrar la llegada.");
    } finally {
        loading.value = false;
    }
}

function solicitarAtender(consulta) {
    if (!esFechaHoy(consulta.fecha)) {
        consultaActiva.value = consulta;
        emergenciaAccion.value = "atender";
        isEmergenciaModal.value = true;
        return;
    }
    cambiarEstado(consulta, "en_atencion");
}

function confirmarEmergencia() {
    const c = consultaActiva.value;
    const accion = emergenciaAccion.value;
    isEmergenciaModal.value = false;
    consultaActiva.value = null;
    emergenciaAccion.value = null;
    if (!c) return;
    if (accion === "check-in") ejecutarCheckIn(c, true);
    else if (accion === "atender") cambiarEstado(c, "en_atencion", true);
    else if (accion === "registro") submitRegistro(true);
}

function esFechaHoy(fecha) {
    return esFechaHoyBolivia(fecha);
}

async function loadRegistroSpecies() {
    try {
        const { data } = await axios.get(route("especies.search"), { params: { search: "" } });
        registroAllSpecies.value = data;
    } catch {
        registroAllSpecies.value = [];
    }
}

async function fetchRegistroBreeds(specieId) {
    if (!specieId) {
        registroBreedsForSpecie.value = [];
        return;
    }
    const { data } = await axios.get(route("razas.search"), { params: { search: "", specie_id: specieId } });
    registroBreedsForSpecie.value = data;
}

function onRegistroSpecieChange() {
    if (registroSpecieSelect.value === "otro") {
        registroSpecieMode.value = "otro";
        registroBreedMode.value = "otro";
        registroBreedSelect.value = "otro";
        registroBreedsForSpecie.value = [];
        return;
    }
    registroSpecieMode.value = "list";
    registroBreedMode.value = "list";
    registroBreedSelect.value = "";
    registroCustomSpecie.value = "";
    registroCustomBreed.value = "";
    fetchRegistroBreeds(registroSpecieSelect.value);
}

function onRegistroBreedChange() {
    registroBreedMode.value = registroBreedSelect.value === "otro" ? "otro" : "list";
}

async function abrirRegistroModal(consulta, emergencia) {
    const cliente = consulta?.mascota?.propietario;
    const mascota = consulta?.mascota;
    consultaActiva.value = consulta;
    registroEmergencia.value = emergencia;
    registroForm.value = {
        first_name: cliente?.nombre || "",
        last_name: cliente?.apellido || "",
        ci: cliente?.ci && cliente.ci !== cliente?.telefono ? cliente.ci : "",
        phone_number: cliente?.telefono || "",
        email: cliente?.email || "",
        gender: "0",
        address: cliente?.direccion || "",
        pet_name: mascota?.nombre || consulta.pet_name || "",
        pet_color: mascota?.color || "",
        pet_gender: "macho",
        pet_age: "",
        pet_weight: mascota?.peso || "",
    };
    registroFormErrors.value = {};
    registroSpecieSelect.value = "";
    registroBreedSelect.value = "";
    await loadRegistroSpecies();
    isRegistroModal.value = true;
}

async function submitRegistro(emergencia = null) {
    const consulta = consultaActiva.value;
    if (!consulta) return;
    loading.value = true;
    registroFormErrors.value = {};
    const payload = { ...registroForm.value, emergencia: emergencia ?? registroEmergencia.value };
    if (registroBreedMode.value === "list" && registroBreedSelect.value && registroBreedSelect.value !== "otro") {
        payload.breed_id = Number(registroBreedSelect.value);
    } else {
        payload.specie = registroCustomSpecie.value.trim();
        payload.breed = registroCustomBreed.value.trim();
    }
    try {
        const { data } = await axios.post(
            route("consultas-medicas.registro-llegada", { id: consulta.id }),
            payload
        );
        displayToast("success", data.message);
        isRegistroModal.value = false;
        consultaActiva.value = null;
        recargarAgenda();
    } catch (e) {
        if (e.response?.status === 422) {
            registroFormErrors.value = e.response.data.errors || {};
        }
        displayToast("danger", e.response?.data?.message || "Revise el formulario de registro.");
    } finally {
        loading.value = false;
    }
}

function abrirReprogramar(consulta, modo = "tarde") {
    consultaActiva.value = consulta;
    reprogramarModo.value = modo;
    reprogramarForm.value = { fecha: "", hora: props.horarios[0] || "09:00" };
    reprogramarErrors.value = {};
    isReprogramarModal.value = true;
}

async function submitReprogramar() {
    const consulta = consultaActiva.value;
    if (!consulta) return;
    loading.value = true;
    reprogramarErrors.value = {};
    const url = reprogramarModo.value === "tarde"
        ? route("consultas-medicas.reprogramar-tarde", { id: consulta.id })
        : route("consultas-medicas.reprogramar", { id: consulta.id });
    try {
        const { data } = await axios.post(url, reprogramarForm.value);
        displayToast("success", data.message);
        isReprogramarModal.value = false;
        consultaActiva.value = null;
        recargarAgenda();
    } catch (e) {
        if (e.response?.status === 422) {
            reprogramarErrors.value = e.response.data.errors || {};
        }
        displayToast("danger", e.response?.data?.message || "No se pudo reprogramar.");
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <AdminLayout title="Agenda del día">
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType" closable>{{ toastMsg }}</FwbToast>
        </div>

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 my-6">
            <div>
                <h2 class="text-2xl font-semibold vet-page-title">Agenda del día</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Cierre del día: {{ horaCierre }} · Sin llegada = anticipo perdido ·
                    <Link :href="route('consultas-medicas.index')" class="text-emerald-700 hover:underline">
                        Ver todas las consultas
                    </Link>
                </p>
            </div>
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <InputLabel value="Fecha agenda" />
                    <input v-model="fechaAgenda" type="date" class="border rounded px-3 py-2" @change="cambiarFechaAgenda" />
                </div>
                <FwbButton color="green" @click="recargarAgenda">
                    <i class="fa-solid fa-rotate mr-2"></i>Actualizar
                </FwbButton>
            </div>
        </div>

        <!-- Recordatorios -->
        <section class="mb-8 rounded-xl border border-emerald-100 bg-white p-5 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-emerald-900">
                        <i class="fa-brands fa-whatsapp text-emerald-600 mr-2"></i>Recordatorios de citas
                    </h3>
                    <p class="text-sm text-gray-500">Envíe manualmente por WhatsApp las citas reservadas del día seleccionado.</p>
                </div>
                <div>
                    <InputLabel value="Citas del día" />
                    <input v-model="fechaRecordatorio" type="date" class="border rounded px-3 py-2" @change="cambiarFechaRecordatorios" />
                </div>
            </div>
            <div v-if="!recordatorios?.length" class="text-sm text-gray-500 py-4 text-center">
                No hay citas reservadas para recordar en esta fecha.
            </div>
            <div v-else class="grid md:grid-cols-2 xl:grid-cols-3 gap-3">
                <div
                    v-for="c in recordatorios"
                    :key="'rec-' + c.id"
                    class="border border-emerald-100 rounded-lg p-3 bg-emerald-50/40"
                >
                    <p class="font-semibold text-emerald-900">{{ horaCita(c) }} — {{ c.pet_name }}</p>
                    <p class="text-sm text-gray-600">{{ c.pet_owner }}</p>
                    <p class="text-xs text-gray-500">{{ c.servicio?.nombre }}</p>
                    <div class="mt-2 flex gap-2">
                        <a
                            v-if="enlaceWhatsApp(c)"
                            :href="enlaceWhatsApp(c)"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center text-sm px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700"
                        >
                            <i class="fa-brands fa-whatsapp mr-1"></i> WhatsApp
                        </a>
                        <span v-else class="text-xs text-red-600">Sin teléfono</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Kanban -->
        <p class="text-sm text-gray-600 mb-4">
            Flujo del día: <strong>Reservada</strong> → Check-in → <strong>En espera</strong> → Atender →
            <strong>En atención</strong> → Ficha → <strong>Completada</strong> → Cobrar.
            La agenda se actualiza sola cada 45 segundos.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 pb-8">
            <div
                v-for="col in columnasKanban"
                :key="col.key"
                class="rounded-xl border p-3 min-h-[320px]"
                :class="col.tone"
            >
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-800">
                        <i :class="['fa-solid', col.icon, 'mr-1']"></i>{{ col.label }}
                    </h3>
                    <FwbBadge type="dark">{{ agenda.columnas[col.key]?.length || 0 }}</FwbBadge>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="c in agenda.columnas[col.key] || []"
                        :key="c.id"
                        class="bg-white rounded-lg border border-gray-100 p-3 shadow-sm"
                    >
                        <div class="flex justify-between items-start gap-2">
                            <p class="font-bold text-gray-900">{{ horaCita(c) }}</p>
                            <span class="text-xs text-gray-400">#{{ c.id }}</span>
                        </div>
                        <p class="font-medium text-emerald-900">{{ c.pet_name }}</p>
                        <p class="text-sm text-gray-600 truncate">{{ c.pet_owner }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ c.servicio?.nombre || c.reason }}</p>
                        <div class="flex flex-wrap items-center gap-1 mt-2">
                            <FwbBadge :type="estadoBadge[c.estado] || 'dark'" class="text-xs">
                                {{ etiquetaEstado(c.estado) }}
                            </FwbBadge>
                            <FwbBadge
                                v-if="c.requiere_registro_llegada"
                                type="warning"
                            >Registro pendiente</FwbBadge>
                            <span
                                v-if="saldoConsulta(c) > 0"
                                class="text-xs text-amber-700 font-medium"
                            >
                                Saldo Bs. {{ saldoConsulta(c).toFixed(2) }}
                            </span>
                        </div>

                        <div v-if="canEdit" class="flex flex-wrap gap-1 mt-3">
                            <template v-if="col.key === 'reservada'">
                                <FwbButton size="xs" color="green" @click="solicitarCheckIn(c)">
                                    Check-in
                                </FwbButton>
                                <FwbButton
                                    v-if="c.puede_reprogramar_tarde"
                                    size="xs"
                                    color="yellow"
                                    @click="abrirReprogramar(c, 'tarde')"
                                >
                                    Llegó tarde
                                </FwbButton>
                                <FwbButton size="xs" color="red" @click="cambiarEstado(c, 'cancelada')">
                                    Cancelar
                                </FwbButton>
                            </template>
                            <template v-if="col.key === 'en_espera'">
                                <FwbButton size="xs" color="blue" @click="solicitarAtender(c)">
                                    Atender
                                </FwbButton>
                            </template>
                            <template v-if="col.key === 'en_atencion'">
                                <Link
                                    :href="urlFichaClinica(c.id)"
                                    class="inline-flex items-center text-xs px-2 py-1 rounded bg-blue-600 text-white hover:bg-blue-700"
                                >
                                    Ficha clínica
                                </Link>
                                <FwbButton size="xs" color="green" @click="cambiarEstado(c, 'completada')">
                                    Completar
                                </FwbButton>
                            </template>
                            <template v-if="col.key === 'completada' && canManagePagos && saldoConsulta(c) > 0">
                                <Link
                                    :href="urlCobrar(c.id)"
                                    class="inline-flex items-center text-xs px-2 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700"
                                >
                                    Cobrar
                                </Link>
                            </template>
                        </div>
                    </div>
                    <p v-if="!(agenda.columnas[col.key]?.length)" class="text-xs text-gray-400 text-center py-6">
                        Sin citas
                    </p>
                </div>
            </div>
        </div>

        <!-- Modal registro -->
        <FwbModal v-if="isRegistroModal" @close="isRegistroModal = false" size="2xl">
            <template #header>Registro en llegada</template>
            <template #body>
                <p class="text-sm text-amber-800 bg-amber-50 rounded-lg px-4 py-3 mb-4">
                    Complete los datos y el paciente pasará a sala de espera.
                </p>
                <div class="grid md:grid-cols-2 gap-4">
                    <div><InputLabel value="Nombre *" /><TextInput v-model="registroForm.first_name" class="mt-1 w-full" /><InputError :message="registroFormErrors.first_name?.[0]" /></div>
                    <div><InputLabel value="Apellido *" /><TextInput v-model="registroForm.last_name" class="mt-1 w-full" /><InputError :message="registroFormErrors.last_name?.[0]" /></div>
                    <div><InputLabel value="CI *" /><TextInput v-model="registroForm.ci" class="mt-1 w-full" /><InputError :message="registroFormErrors.ci?.[0]" /></div>
                    <div><InputLabel value="Teléfono *" /><TextInput v-model="registroForm.phone_number" class="mt-1 w-full" /></div>
                    <div><InputLabel value="Color mascota *" /><TextInput v-model="registroForm.pet_color" class="mt-1 w-full" /><InputError :message="registroFormErrors.pet_color?.[0]" /></div>
                    <div>
                        <InputLabel value="Especie *" />
                        <select v-model="registroSpecieSelect" class="mt-1 w-full border rounded px-2 py-2" @change="onRegistroSpecieChange">
                            <option value="">Seleccione</option>
                            <option v-for="s in registroAllSpecies" :key="s.id" :value="s.id">{{ s.name || s.nombre }}</option>
                            <option value="otro">Otra...</option>
                        </select>
                        <TextInput v-if="registroSpecieMode === 'otro'" v-model="registroCustomSpecie" class="mt-2 w-full" placeholder="Especie" />
                    </div>
                    <div>
                        <InputLabel value="Raza *" />
                        <select v-if="registroSpecieMode !== 'otro' && registroBreedMode === 'list'" v-model="registroBreedSelect" class="mt-1 w-full border rounded px-2 py-2" @change="onRegistroBreedChange">
                            <option value="">Seleccione</option>
                            <option v-for="b in registroBreedsForSpecie" :key="b.id" :value="b.id">{{ b.name || b.nombre }}</option>
                            <option value="otro">Otra...</option>
                        </select>
                        <TextInput v-else v-model="registroCustomBreed" class="mt-1 w-full" placeholder="Raza" />
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <FwbButton color="alternative" @click="isRegistroModal = false">Cancelar</FwbButton>
                    <FwbButton color="blue" :loading="loading" @click="submitRegistro()">Registrar y poner en espera</FwbButton>
                </div>
            </template>
        </FwbModal>

        <!-- Modal reprogramar -->
        <FwbModal v-if="isReprogramarModal" @close="isReprogramarModal = false">
            <template #header>Reprogramar por llegada tarde — #{{ consultaActiva?.id }}</template>
            <template #body>
                <p class="text-sm text-sky-800 bg-sky-50 border border-sky-100 rounded-lg px-4 py-3 mb-4">
                    Única reprogramación permitida. El anticipo pagado se conserva.
                </p>
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Nueva fecha *" />
                        <input v-model="reprogramarForm.fecha" type="date" class="mt-1 w-full border rounded px-3 py-2" :min="ahoraFechaYmd()" />
                        <InputError :message="reprogramarErrors.fecha?.[0]" />
                    </div>
                    <div>
                        <InputLabel value="Nuevo horario *" />
                        <select v-model="reprogramarForm.hora" class="mt-1 w-full border rounded px-3 py-2">
                            <option v-for="h in horarios" :key="h" :value="h">{{ h }}</option>
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

        <!-- Modal emergencia -->
        <FwbModal v-if="isEmergenciaModal" @close="isEmergenciaModal = false">
            <template #header>Fuera de fecha de la cita</template>
            <template #body>
                <p class="text-center">Esta acción es para el día de la cita. ¿Continuar de todas formas?</p>
            </template>
            <template #footer>
                <div class="flex justify-center gap-2">
                    <FwbButton color="alternative" @click="isEmergenciaModal = false">Volver</FwbButton>
                    <FwbButton color="red" :loading="loading" @click="confirmarEmergencia">Continuar</FwbButton>
                </div>
            </template>
        </FwbModal>
    </AdminLayout>
</template>
