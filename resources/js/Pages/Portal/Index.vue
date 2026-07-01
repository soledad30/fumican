<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link, router } from "@inertiajs/vue3";
import { FwbBadge, FwbButton, FwbModal, FwbToast } from "flowbite-vue";
import { computed, inject, ref } from "vue";
import axios from "axios";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";

const route = inject("route");

const props = defineProps({
    cliente: Object,
    mascotas: Array,
    servicios: { type: Array, default: () => [] },
    horarios: { type: Array, default: () => [] },
    deudas: { type: Array, default: () => [] },
    compras: { type: Array, default: () => [] },
});

const showReservaModal = ref(false);
const loading = ref(false);
const showToast = ref(false);
const toastMsg = ref("");
const toastType = ref("success");
const formErrors = ref({});

const form = ref({
    mascota_id: "",
    servicio_id: "",
    fecha: "",
    hora: "",
    comentario: "",
});

const fechaMinima = computed(() => new Date().toISOString().split("T")[0]);

const servicioSeleccionado = computed(() =>
    props.servicios.find((s) => String(s.id) === String(form.value.servicio_id))
);

function iconoServicio(nombre) {
    const n = (nombre || "").toLowerCase();
    if (n.includes("vacun")) return "fa-solid fa-syringe";
    if (n.includes("baño") || n.includes("bano") || n.includes("pelu")) return "fa-solid fa-scissors";
    if (n.includes("cirug")) return "fa-solid fa-kit-medical";
    if (n.includes("consult") || n.includes("revision") || n.includes("revisión")) return "fa-solid fa-stethoscope";
    return "fa-solid fa-paw";
}

function abrirReserva(mascotaId = "") {
    form.value = {
        mascota_id: mascotaId ? String(mascotaId) : "",
        servicio_id: "",
        fecha: "",
        hora: "",
        comentario: "",
    };
    formErrors.value = {};
    showReservaModal.value = true;
}

function toast(type, msg) {
    toastType.value = type;
    toastMsg.value = msg;
    showToast.value = true;
    setTimeout(() => (showToast.value = false), 4000);
}

function formatoCita(cita) {
    const fecha = cita.fecha ? new Date(cita.fecha + "T12:00:00").toLocaleDateString() : "";
    const hora = cita.hora ? ` ${cita.hora}` : "";
    const servicio = cita.servicio || cita.motivo || "Consulta";
    return `${servicio} — ${fecha}${hora}`;
}

async function enviarReserva() {
    loading.value = true;
    formErrors.value = {};

    try {
        const { data } = await axios.post(route("portal.reservas.store"), form.value);
        toast("success", data.message || "Cita reservada correctamente.");
        showReservaModal.value = false;
        router.reload({ only: ["mascotas"] });
    } catch (e) {
        if (e.response?.status === 422) {
            formErrors.value = e.response.data.errors ?? {};
            toast("danger", "Revise los datos de la reserva.");
        } else {
            toast("danger", e.response?.data?.message || "No se pudo reservar la cita.");
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <AdminLayout title="Mi cuenta">
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType" closable>{{ toastMsg }}</FwbToast>
        </div>

        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold vet-page-title">Hola, {{ cliente.full_name }}</h2>
                <p class="vet-page-subtitle mt-1">
                    Reserva citas para tus mascotas y consulta su carnet sanitario.
                </p>
            </div>
            <FwbButton
                v-if="mascotas?.length"
                color="green"
                @click="abrirReserva()"
            >
                <i class="fa-solid fa-calendar-plus mr-2"></i>
                Reservar cita
            </FwbButton>
        </div>

        <div v-if="!mascotas?.length" class="vet-panel p-8 text-center">
            <p class="text-gray-600">Aún no tiene mascotas registradas en la clínica.</p>
            <p class="text-sm text-gray-500 mt-2">Solicite el registro en recepción en su próxima visita.</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <div
                v-for="m in mascotas"
                :key="m.id"
                class="vet-panel p-4 flex flex-col gap-3"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="w-14 h-14 rounded-full bg-emerald-100 flex items-center justify-center overflow-hidden shrink-0"
                    >
                        <img
                            v-if="m.photo_url"
                            :src="m.photo_url"
                            :alt="m.nombre"
                            class="w-full h-full object-cover"
                        />
                        <i v-else class="fa-solid fa-paw text-emerald-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-lg">{{ m.nombre }}</h3>
                        <p class="text-sm text-gray-500">
                            {{ m.especie }} — {{ m.raza }}
                        </p>
                        <p v-if="m.peso" class="text-sm text-gray-500">Peso: {{ m.peso }} kg</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <FwbBadge v-if="m.alertas_sanitarias" type="red">
                        {{ m.alertas_sanitarias }} alerta(s) sanitaria(s)
                    </FwbBadge>
                    <FwbBadge v-if="m.tiene_citas" type="yellow">Cita programada</FwbBadge>
                    <FwbBadge v-if="!m.alertas_sanitarias && !m.tiene_citas" type="green">
                        Al día
                    </FwbBadge>
                </div>

                <div v-if="m.proximas_citas?.length" class="text-sm bg-amber-50 border border-amber-100 rounded-lg p-3">
                    <p class="font-medium text-amber-900 mb-1">Próximas citas</p>
                    <ul class="space-y-1 text-amber-800">
                        <li v-for="c in m.proximas_citas" :key="c.id">
                            {{ formatoCita(c) }}
                        </li>
                    </ul>
                </div>

                <div class="flex flex-col sm:flex-row gap-2 mt-auto">
                    <FwbButton
                        color="alternative"
                        class="flex-1"
                        @click="abrirReserva(m.id)"
                    >
                        <i class="fa-solid fa-calendar-plus mr-2"></i>
                        Reservar
                    </FwbButton>
                    <FwbButton
                        tag="a"
                        :href="route('portal.carnet', m.id)"
                        color="green"
                        class="flex-1"
                    >
                        <i class="fa-solid fa-id-card mr-2"></i>
                        Carnet
                    </FwbButton>
                </div>
            </div>
        </div>

        <div v-if="deudas?.length" class="vet-panel p-4 sm:p-6 mt-6 border-amber-200 bg-amber-50/50">
            <h3 class="font-semibold text-lg mb-3 text-amber-900">Pagos pendientes</h3>
            <ul class="space-y-2 text-sm">
                <li v-for="d in deudas" :key="`${d.tipo}-${d.id}`" class="flex justify-between gap-4">
                    <span>{{ d.descripcion }}</span>
                    <strong class="text-amber-800">Bs. {{ Number(d.saldo).toFixed(2) }}</strong>
                </li>
            </ul>
            <p class="text-xs text-amber-700 mt-3">Acuda a recepción para registrar su pago. Los clientes no pueden eliminar pagos.</p>
        </div>

        <div v-if="compras?.length" class="vet-panel p-4 sm:p-6 mt-6">
            <h3 class="font-semibold text-lg mb-3">Mis compras recientes</h3>
            <ul class="space-y-2 text-sm">
                <li v-for="c in compras" :key="c.id" class="flex justify-between gap-4 border-b pb-2">
                    <span>Nota #{{ c.id }} — {{ c.fecha }}</span>
                    <span>
                        Total Bs. {{ Number(c.total).toFixed(2) }}
                        <span v-if="c.saldo > 0" class="text-amber-700">(saldo Bs. {{ Number(c.saldo).toFixed(2) }})</span>
                    </span>
                </li>
            </ul>
        </div>

        <div v-if="servicios?.length" class="vet-panel p-4 sm:p-6 mt-6">
            <h3 class="font-semibold text-lg mb-3">Servicios disponibles</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div
                    v-for="s in servicios"
                    :key="s.id"
                    class="flex items-start gap-3 p-3 rounded-lg border bg-white/60"
                >
                    <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0">
                        <i :class="iconoServicio(s.nombre)" class="text-emerald-700"></i>
                    </div>
                    <div>
                        <p class="font-medium">{{ s.nombre }}</p>
                        <p v-if="s.descripcion" class="text-xs text-gray-500 mt-0.5">{{ s.descripcion }}</p>
                        <p v-if="s.precio != null" class="text-sm text-emerald-700 mt-1">
                            Desde Bs. {{ Number(s.precio).toFixed(2) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <FwbModal v-if="showReservaModal" size="lg" @close="showReservaModal = false">
            <template #header>
                <h3 class="text-xl font-semibold">Reservar cita</h3>
            </template>
            <template #body>
                <form class="space-y-4" @submit.prevent="enviarReserva">
                    <div>
                        <InputLabel value="Mascota" />
                        <select
                            v-model="form.mascota_id"
                            class="mt-1 w-full border rounded-md p-2.5 dark:bg-gray-700 dark:border-gray-600"
                            required
                        >
                            <option value="" disabled>Seleccione su mascota</option>
                            <option v-for="m in mascotas" :key="m.id" :value="m.id">
                                {{ m.nombre }} ({{ m.especie }})
                            </option>
                        </select>
                        <InputError :message="formErrors.mascota_id?.[0]" />
                    </div>

                    <div>
                        <InputLabel value="Servicio" />
                        <select
                            v-model="form.servicio_id"
                            class="mt-1 w-full border rounded-md p-2.5 dark:bg-gray-700 dark:border-gray-600"
                            required
                        >
                            <option value="" disabled>Revisión, baño, vacuna, etc.</option>
                            <option v-for="s in servicios" :key="s.id" :value="s.id">
                                {{ s.nombre }}
                                <template v-if="s.precio != null"> — Bs. {{ Number(s.precio).toFixed(2) }}</template>
                            </option>
                        </select>
                        <InputError :message="formErrors.servicio_id?.[0]" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Fecha" />
                            <TextInput
                                v-model="form.fecha"
                                type="date"
                                :min="fechaMinima"
                                class="mt-1 w-full"
                                required
                            />
                            <InputError :message="formErrors.fecha?.[0]" />
                        </div>
                        <div>
                            <InputLabel value="Horario" />
                            <select
                                v-model="form.hora"
                                class="mt-1 w-full border rounded-md p-2.5 dark:bg-gray-700 dark:border-gray-600"
                                required
                            >
                                <option value="" disabled>Seleccione hora</option>
                                <option v-for="h in horarios" :key="h" :value="h">{{ h }}</option>
                            </select>
                            <InputError :message="formErrors.hora?.[0]" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Comentario (opcional)" />
                        <textarea
                            v-model="form.comentario"
                            rows="3"
                            class="mt-1 w-full border rounded-md p-2.5 dark:bg-gray-700 dark:border-gray-600"
                            placeholder="Ej. baño antipulgas, revisión general, vacuna anual..."
                        ></textarea>
                        <InputError :message="formErrors.comentario?.[0]" />
                    </div>

                    <p v-if="servicioSeleccionado" class="text-sm text-gray-600">
                        Referencia: <strong>{{ servicioSeleccionado.nombre }}</strong>
                        <span v-if="servicioSeleccionado.precio != null">
                            — Bs. {{ Number(servicioSeleccionado.precio).toFixed(2) }}
                        </span>
                    </p>
                    <p class="text-sm text-emerald-800 bg-emerald-50 border border-emerald-100 rounded-lg p-3">
                        No se cobra al reservar. Acuda a la clínica en la fecha indicada;
                        al finalizar el servicio podrá pagar en recepción (efectivo, tarjeta,
                        transferencia, QR o crédito en cuotas).
                    </p>
                </form>
            </template>
            <template #footer>
                <div class="flex justify-end gap-2 w-full">
                    <FwbButton color="alternative" @click="showReservaModal = false">Cancelar</FwbButton>
                    <FwbButton color="green" :disabled="loading" @click="enviarReserva">
                        Confirmar reserva
                    </FwbButton>
                </div>
            </template>
        </FwbModal>
    </AdminLayout>
</template>
