<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { router } from "@inertiajs/vue3";
import { ref, watch, computed, inject } from "vue";
import axios from "axios";
import {
    FwbButton, FwbTable, FwbTableHead, FwbTableHeadCell,
    FwbTableBody, FwbTableRow, FwbTableCell, FwbPagination,
    FwbModal, FwbToast, FwbBadge,
} from "flowbite-vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import TableActionButtons from "@/Components/TableActionButtons.vue";
import { usePermisos } from "@/Composables/usePermisos";
import { usePlanCredito } from "@/Composables/usePlanCredito";
import { usePagoQr, aplicarInfoPagoQrAForm } from "@/Composables/usePagoQr";
import PagoQrConfirmacionModal from "@/Components/Modals/PagoQrConfirmacionModal.vue";
import { ahoraDatetimeLocal, formatearFechaHora, paraDatetimeLocal } from "@/Utils/fechaBolivia";

const route = inject("route");
const { puede } = usePermisos();
const canCreatePagos = computed(() => puede("gestionar pagos") || puede("crear pagos"));
const canEditPagos = computed(() => puede("editar pagos"));

const props = defineProps({
    pagos: Object,
    estadisticas: Object,
    tiposPago: Object,
    metodosPago: Object,
    notasVenta: Array,
    consultas: Array,
    cuentasPendientes: { type: Array, default: () => [] },
    planesPago: Array,
    filters: Object,
});

const filters = ref({
    search_term: props.filters.search_term || "",
    tipo_pago: props.filters.tipo_pago || "",
});
const currentPage = ref(props.pagos.current_page || 1);
const showToast = ref(false);
const toastMsg = ref("");
const toastType = ref("success");
const isModal = ref(false);
const isDeleteModal = ref(false);
const loading = ref(false);
const selected = ref(null);
const origenPago = ref("nota");
const form = ref({
    nota_venta_id: "", consulta_id: "", monto: 0, tipo_pago: "contado", metodo_pago: "efectivo",
    id_transaccion_externa: "", fecha_pago: "", num_cuotas: 3,
});
const isCuotaModal = ref(false);
const cuotaForm = ref({ monto: 0, metodo_pago: "efectivo", id_transaccion_externa: "", fecha_pago: "" });
const selectedCuota = ref(null);

const notasParaCobro = computed(() =>
    props.notasVenta.filter((n) => Number(n.saldo_pendiente ?? n.monto_total ?? 0) > 0)
);
const consultasParaCobro = computed(() =>
    props.consultas.filter((c) => Number(c.saldo_pendiente ?? 0) > 0)
);

const origenSeleccionado = computed(() => {
    if (origenPago.value === "nota" && form.value.nota_venta_id) {
        return props.notasVenta.find((n) => String(n.id) === String(form.value.nota_venta_id));
    }
    if (origenPago.value === "consulta" && form.value.consulta_id) {
        return props.consultas.find((c) => String(c.id) === String(form.value.consulta_id));
    }
    return null;
});

const resumenCreditoParcial = computed(() => {
    if (form.value.tipo_pago !== "credito" || !origenSeleccionado.value || selected.value) return null;
    if (pagosPlan.value.length) return null;
    const saldo = saldoOrigen(origenSeleccionado.value);
    const monto = Number(form.value.monto) || 0;
    const cuotas = Number(form.value.num_cuotas) || 1;
    if (monto <= 0 || monto >= saldo - 0.009) return null;
    const restante = Math.max(0, Math.round((saldo - monto) * 100) / 100);
    return {
        cobraHoy: monto,
        restante,
        cuotas,
        porCuota: cuotas > 1 ? Math.round((restante / cuotas) * 100) / 100 : restante,
    };
});

const {
    numPagos,
    pagosPlan,
    initPlan,
    rebuildPlan,
    sumaPlan,
    diferencia,
    planValido,
    saldosPorFila,
    maxFechaVencimiento,
    payloadCuotasPlan,
} = usePlanCredito(() => saldoOrigen(origenSeleccionado.value));

watch(currentPage, (page) => {
    router.get(route("pagos.search"), { ...filters.value, page }, { preserveState: true });
});

function toast(type, msg) {
    toastType.value = type; toastMsg.value = msg; showToast.value = true;
    setTimeout(() => (showToast.value = false), 3000);
}

const {
    pagoQrImage,
    pagoQrTransaccion,
    pagoQrNumeroPago,
    pagoQrVerificando,
    pagoQrEsperando,
    pagoQrVerificacionLista,
    pagoQrInfo,
    showConfirmacionPago,
    limpiarQrPago,
    generarQrPago: generarQrApi,
    verificarQrPago,
    iniciarVerificacionQrPago,
    reintentarVerificacionQrPago,
    cerrarConfirmacionPago,
    textoEstadoVerificacionQr,
} = usePagoQr({
    onError: (msg) => toast("danger", msg),
});

function applyFilters() {
    router.get(route("pagos.search"), filters.value, { preserveState: true, replace: true });
}

function tipoBadge(tipo) {
    return tipo === "credito" ? "yellow" : "green";
}

function formatFechaPago(fecha) {
    return formatearFechaHora(fecha);
}

function saldoOrigen(item) {
    if (!item) return 0;
    return Number(item.saldo_pendiente ?? item.saldo ?? 0);
}

function totalOrigen(item) {
    if (!item) return 0;
    if (item.monto_total != null) return Number(item.monto_total);
    return Number(item.costo_consulta ?? item.consultation_fee ?? 0);
}

function pagadoOrigen(item) {
    if (!item) return 0;
    return Number(item.monto_pagado ?? item.pagado ?? 0);
}

function notaVentaLabel(nota) {
    const cliente = nota.cliente ? `${nota.cliente.nombre} ${nota.cliente.apellido || ""}`.trim() : "Sin cliente";
    const total = totalOrigen(nota).toFixed(2);
    const pagado = pagadoOrigen(nota).toFixed(2);
    const saldo = saldoOrigen(nota).toFixed(2);
    return `#${nota.id} — ${cliente} — Total Bs. ${total} | Pagado Bs. ${pagado} | Saldo Bs. ${saldo}`;
}

function consultaLabel(c) {
    const mascota = c.mascota?.nombre || c.pet?.name || "Sin mascota";
    const servicio = c.servicio?.nombre || "Sin servicio";
    const total = Number(c.costo_consulta ?? c.consultation_fee ?? 0).toFixed(2);
    const pagado = Number(c.monto_pagado ?? 0).toFixed(2);
    const saldo = Number(c.saldo_pendiente ?? 0).toFixed(2);
    return `#${c.id} — ${mascota} (${servicio}) — Total Bs. ${total} | Pagado Bs. ${pagado} | Saldo Bs. ${saldo}`;
}

function pagoOrigen(p) {
    if (p.consulta_id) {
        const mascota = p.consulta?.mascota?.nombre || p.mascota?.nombre || "—";
        return `Consulta #${p.consulta_id} (${mascota})`;
    }
    if (p.nota_venta_id) return `Nota #${p.nota_venta_id}`;
    return "—";
}

function pagoCliente(p) {
    return p.cliente?.nombre
        ? `${p.cliente.nombre} ${p.cliente.apellido || ""}`.trim()
        : p.nota_venta?.cliente?.full_name
        || p.nota_venta?.cliente?.nombre
        || p.consulta?.mascota?.cliente?.nombre
        || "—";
}

function cerrarModalPago() {
    limpiarQrPago();
    isModal.value = false;
}

function datosClienteQr() {
    if (origenPago.value === "consulta" && origenSeleccionado.value) {
        const c = origenSeleccionado.value;
        const cliente = c.mascota?.propietario ?? c.mascota?.cliente ?? c.mascota?.owner;
        return {
            name: `${cliente?.nombre || ""} ${cliente?.apellido || ""}`.trim() || "Cliente Fumican",
            phone: String(cliente?.telefono || cliente?.ci || "70000000"),
            email: cliente?.email || "pagos@fumican.bo",
            descripcion: c.servicio?.nombre || `Consulta #${c.id}`,
            serviceId: c.servicio_id || c.service_id,
        };
    }
    if (origenPago.value === "nota" && origenSeleccionado.value) {
        const n = origenSeleccionado.value;
        const cliente = n.cliente;
        return {
            name: `${cliente?.nombre || ""} ${cliente?.apellido || ""}`.trim() || "Cliente Fumican",
            phone: String(cliente?.telefono || cliente?.ci || "70000000"),
            email: cliente?.email || "pagos@fumican.bo",
            descripcion: `Nota de venta #${n.id}`,
        };
    }
    return { name: "Cliente Fumican", phone: "70000000", email: "pagos@fumican.bo", descripcion: "Pago Fumican Vet" };
}

function openCreate() {
    selected.value = null;
    limpiarQrPago();
    origenPago.value = "nota";
    form.value = {
        nota_venta_id: "", consulta_id: "", monto: 0, tipo_pago: "contado", metodo_pago: "efectivo",
        id_transaccion_externa: "", fecha_pago: ahoraDatetimeLocal(), num_cuotas: 3,
    };
    pagosPlan.value = [];
    isModal.value = true;
}

function openCreatePendiente(cuenta) {
    selected.value = null;
    limpiarQrPago();
    origenPago.value = cuenta.tipo === "consulta" ? "consulta" : "nota";
    form.value = {
        nota_venta_id: cuenta.tipo === "nota" ? cuenta.id : "",
        consulta_id: cuenta.tipo === "consulta" ? cuenta.id : "",
        monto: Number(cuenta.saldo) || 0,
        tipo_pago: "contado",
        metodo_pago: "efectivo",
        id_transaccion_externa: "",
        fecha_pago: ahoraDatetimeLocal(),
        num_cuotas: 3,
    };
    pagosPlan.value = [];
    isModal.value = true;
}

function openEdit(p) {
    selected.value = p;
    origenPago.value = p.consulta_id ? "consulta" : "nota";
    form.value = {
        nota_venta_id: p.nota_venta_id || "",
        consulta_id: p.consulta_id || "",
        monto: p.monto,
        tipo_pago: p.tipo_pago || "contado",
        metodo_pago: p.metodo_pago || "efectivo",
        id_transaccion_externa: p.id_transaccion_externa || "",
        fecha_pago: p.fecha_pago ? paraDatetimeLocal(p.fecha_pago) : "",
    };
    isModal.value = true;
}

watch(() => form.value.consulta_id, (id) => {
    if (!id || selected.value) return;
    const c = props.consultas.find((x) => String(x.id) === String(id));
    if (c) {
        const saldo = saldoOrigen(c);
        if (saldo > 0) form.value.monto = saldo;
    }
});

watch(() => form.value.nota_venta_id, (id) => {
    if (!id || selected.value) return;
    const n = props.notasVenta.find((x) => String(x.id) === String(id));
    if (n) {
        const saldo = saldoOrigen(n);
        if (saldo > 0) form.value.monto = saldo;
    }
});

watch(origenPago, (origen) => {
    if (origen === "nota") form.value.consulta_id = "";
    else form.value.nota_venta_id = "";
    if (!selected.value) {
        form.value.monto = 0;
        limpiarQrPago();
    }
});

watch(() => form.value.metodo_pago, (metodo) => {
    if (metodo !== "qr") limpiarQrPago();
});

watch(() => form.value.tipo_pago, (tipo) => {
    if (!selected.value && tipo === "contado" && !form.value.fecha_pago) {
        form.value.fecha_pago = ahoraDatetimeLocal();
    }
    if (!selected.value && tipo === "credito" && origenSeleccionado.value) {
        initPlan();
    }
    if (tipo === "contado") {
        pagosPlan.value = [];
    }
});

watch(origenSeleccionado, (origen) => {
    if (!selected.value && form.value.tipo_pago === "credito" && origen) {
        initPlan();
    }
});

async function generarQrPago() {
    const monto = Number(form.value.monto);
    if (!monto || monto <= 0) {
        toast("danger", "Indique un monto válido para generar el QR.");
        return false;
    }
    const ok = await generarQrApi({ ...datosClienteQr(), monto });
    if (ok) {
        form.value.id_transaccion_externa = pagoQrTransaccion.value || pagoQrNumeroPago.value || "";
    }
    return ok;
}

async function recargarDatosPagos() {
    await router.reload({
        preserveScroll: true,
        only: ["notasVenta", "consultas", "cuentasPendientes", "estadisticas", "pagos", "planesPago"],
    });
}

async function confirmarPagoQrYGuardar() {
    if (pagoQrInfo.value) {
        aplicarInfoPagoQrAForm(form.value, pagoQrInfo.value, pagosPlan.value);
    }
    cerrarConfirmacionPago();
    await guardarPago();
}

async function guardarPago() {
    if (!selected.value && form.value.tipo_pago === "credito" && pagosPlan.value.length) {
        if (!planValido.value) {
            toast("danger", "La suma de los pagos debe igualar el saldo pendiente.");
            return;
        }
    }

    loading.value = true;
    try {
        const url = selected.value
            ? route("pagos.update", { id: selected.value.id })
            : route("pagos.store");
        const method = selected.value ? axios.put : axios.post;
        const payload = { ...form.value };
        if (!selected.value && form.value.tipo_pago === "credito" && pagosPlan.value.length) {
            payload.cuotas_plan = payloadCuotasPlan();
            payload.monto = Number(pagosPlan.value[0]?.monto) || payload.monto;
            payload.fecha_pago = pagosPlan.value[0]?.fecha || payload.fecha_pago;
        }
        const { data } = await method(url, payload);
        toast("success", data.message);
        cerrarModalPago();
        await recargarDatosPagos();
    } catch (e) {
        toast("danger", e.response?.data?.message || e.response?.data?.errors?.monto?.[0] || "Error al guardar pago");
    } finally {
        loading.value = false;
    }
}

async function submit() {
    if (!selected.value && form.value.metodo_pago === "qr") {
        if (!form.value.nota_venta_id && !form.value.consulta_id) {
            toast("danger", "Seleccione una nota de venta o consulta médica.");
            return;
        }
        if (!pagoQrImage.value) {
            loading.value = true;
            const ok = await generarQrPago();
            loading.value = false;
            if (ok) {
                toast("success", "Escanee el QR con la app bancaria del cliente.");
                iniciarVerificacionQrPago();
            }
            return;
        }
        if (!pagoQrVerificando.value && !pagoQrEsperando.value) {
            loading.value = true;
            const pagado = pagoQrVerificacionLista.value
                ? (await reintentarVerificacionQrPago()).pagado
                : await verificarQrPago();
            loading.value = false;
            if (pagado) return;
            if (!pagoQrVerificacionLista.value) {
                toast("danger", "Pago aún no confirmado. Pulse «Verificar pago» cuando el cliente haya pagado.");
            }
        }
        return;
    }
    await guardarPago();
}

function openDelete(p) { selected.value = p; isDeleteModal.value = true; }

async function confirmDelete() {
    loading.value = true;
    try {
        const { data } = await axios.delete(route("pagos.destroy", { id: selected.value.id }));
        toast("success", data.message);
        isDeleteModal.value = false;
        await recargarDatosPagos();
    } catch (e) {
        toast("danger", e.response?.data?.message || "Error al eliminar");
    } finally { loading.value = false; }
}

function openPagarCuota(cuota) {
    selectedCuota.value = cuota;
    cuotaForm.value = {
        monto: Number(cuota.monto) || 0,
        metodo_pago: "efectivo",
        id_transaccion_externa: "",
        fecha_pago: ahoraDatetimeLocal(),
    };
    isCuotaModal.value = true;
}

async function submitCuota() {
    loading.value = true;
    try {
        const { data } = await axios.post(route("pagos.cuotas.pagar", selectedCuota.value.id), cuotaForm.value);
        toast("success", data.message);
        isCuotaModal.value = false;
        await recargarDatosPagos();
    } catch (e) {
        toast("danger", e.response?.data?.message || "Error al registrar cuota");
    } finally { loading.value = false; }
}
</script>

<template>
    <AdminLayout title="Gestión de Pagos">
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType">{{ toastMsg }}</FwbToast>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="p-4 bg-green-50 dark:bg-green-900 rounded-lg">
                <p class="text-sm text-green-700 dark:text-green-300">Total cobrado (contado)</p>
                <p class="text-2xl font-bold text-green-800 dark:text-green-100">Bs. {{ Number(estadisticas?.total_contado || 0).toFixed(2) }}</p>
            </div>
            <div class="p-4 bg-yellow-50 dark:bg-yellow-900 rounded-lg">
                <p class="text-sm text-yellow-700 dark:text-yellow-300">Total a crédito</p>
                <p class="text-2xl font-bold text-yellow-800 dark:text-yellow-100">Bs. {{ Number(estadisticas?.total_credito || 0).toFixed(2) }}</p>
            </div>
            <div class="p-4 bg-red-50 dark:bg-red-900 rounded-lg">
                <p class="text-sm text-red-700 dark:text-red-300">Saldo pendiente por cobrar</p>
                <p class="text-2xl font-bold text-red-800 dark:text-red-100">Bs. {{ Number(estadisticas?.total_pendiente || 0).toFixed(2) }}</p>
                <p class="text-xs text-red-600 mt-1">{{ estadisticas?.cuentas_pendientes || 0 }} cuenta(s)</p>
            </div>
            <div class="p-4 bg-blue-50 dark:bg-blue-900 rounded-lg">
                <p class="text-sm text-blue-700 dark:text-blue-300">Créditos sin fecha de pago</p>
                <p class="text-2xl font-bold text-blue-800 dark:text-blue-100">{{ estadisticas?.pendientes_credito || 0 }}</p>
            </div>
        </div>

        <div v-if="cuentasPendientes?.length" class="mb-8">
            <h3 class="text-lg font-semibold mb-3 text-gray-800 dark:text-gray-100">
                Cuentas pendientes de pago
            </h3>
            <FwbTable>
                <FwbTableHead>
                    <FwbTableHeadCell>Tipo</FwbTableHeadCell>
                    <FwbTableHeadCell>Referencia</FwbTableHeadCell>
                    <FwbTableHeadCell>Cliente</FwbTableHeadCell>
                    <FwbTableHeadCell>Descripción</FwbTableHeadCell>
                    <FwbTableHeadCell>Total</FwbTableHeadCell>
                    <FwbTableHeadCell>Pagado</FwbTableHeadCell>
                    <FwbTableHeadCell>Saldo</FwbTableHeadCell>
                    <FwbTableHeadCell>Acción</FwbTableHeadCell>
                </FwbTableHead>
                <FwbTableBody>
                    <FwbTableRow v-for="c in cuentasPendientes" :key="`${c.tipo}-${c.id}`">
                        <FwbTableCell>
                            <FwbBadge :type="c.tipo === 'consulta' ? 'blue' : 'purple'">
                                {{ c.tipo === "consulta" ? "Servicio" : "Productos" }}
                            </FwbBadge>
                        </FwbTableCell>
                        <FwbTableCell>{{ c.referencia }}</FwbTableCell>
                        <FwbTableCell>{{ c.cliente }}</FwbTableCell>
                        <FwbTableCell>{{ c.descripcion }}</FwbTableCell>
                        <FwbTableCell>Bs. {{ Number(c.total).toFixed(2) }}</FwbTableCell>
                        <FwbTableCell>Bs. {{ Number(c.pagado).toFixed(2) }}</FwbTableCell>
                        <FwbTableCell class="font-semibold text-red-600">Bs. {{ Number(c.saldo).toFixed(2) }}</FwbTableCell>
                        <FwbTableCell>
                            <FwbButton
                                v-if="canCreatePagos"
                                size="xs"
                                color="green"
                                type="button"
                                @click="openCreatePendiente(c)"
                            >
                                Cobrar
                            </FwbButton>
                        </FwbTableCell>
                    </FwbTableRow>
                </FwbTableBody>
            </FwbTable>
        </div>

        <h3 class="text-lg font-semibold mb-3 text-gray-800 dark:text-gray-100">Pagos registrados</h3>

        <div class="flex justify-between items-center mb-4">
            <div class="flex gap-2 flex-wrap">
                <TextInput v-model="filters.search_term" placeholder="Buscar nota/cliente/transacción..." class="w-56" />
                <select v-model="filters.tipo_pago" class="border rounded px-2 py-1 text-sm dark:bg-gray-700 dark:border-gray-600">
                    <option value="">Todos los tipos</option>
                    <option v-for="(label, key) in tiposPago" :key="key" :value="key">{{ label }}</option>
                </select>
                <FwbButton @click="applyFilters">Filtrar</FwbButton>
            </div>
            <FwbButton color="green" @click="openCreate" v-if="canCreatePagos">Nuevo pago</FwbButton>
        </div>

        <FwbTable>
            <FwbTableHead>
                <FwbTableHeadCell>Origen</FwbTableHeadCell>
                <FwbTableHeadCell>Cliente</FwbTableHeadCell>
                <FwbTableHeadCell>Monto</FwbTableHeadCell>
                <FwbTableHeadCell>Tipo</FwbTableHeadCell>
                <FwbTableHeadCell>Método</FwbTableHeadCell>
                <FwbTableHeadCell>Transacción</FwbTableHeadCell>
                <FwbTableHeadCell>Fecha</FwbTableHeadCell>
                <FwbTableHeadCell>Acciones</FwbTableHeadCell>
            </FwbTableHead>
            <FwbTableBody>
                <FwbTableRow v-for="p in pagos.data" :key="p.id">
                    <FwbTableCell>{{ pagoOrigen(p) }}</FwbTableCell>
                    <FwbTableCell>{{ pagoCliente(p) }}</FwbTableCell>
                    <FwbTableCell>Bs. {{ Number(p.monto).toFixed(2) }}</FwbTableCell>
                    <FwbTableCell><FwbBadge :type="tipoBadge(p.tipo_pago)">{{ tiposPago[p.tipo_pago] || p.tipo_pago }}</FwbBadge></FwbTableCell>
                    <FwbTableCell>{{ metodosPago[p.metodo_pago] || p.metodo_pago }}</FwbTableCell>
                    <FwbTableCell>{{ p.id_transaccion_externa || "—" }}</FwbTableCell>
                    <FwbTableCell>{{ formatFechaPago(p.fecha_pago) }}</FwbTableCell>
                    <FwbTableCell>
                        <TableActionButtons
                            :can-edit="canEditPagos"
                            :can-delete="canEditPagos"
                            @edit="openEdit(p)"
                            @delete="openDelete(p)"
                        />
                    </FwbTableCell>
                </FwbTableRow>
            </FwbTableBody>
        </FwbTable>

        <div class="mt-4 flex justify-center">
            <FwbPagination v-model="currentPage" :total-pages="pagos.last_page" />
        </div>

        <FwbModal v-if="isModal" @close="cerrarModalPago" size="5xl">
            <template #header><h3>{{ selected ? "Editar" : "Nuevo" }} pago</h3></template>
            <template #body>
                <div class="modal-pago-cuerpo">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <InputLabel value="Origen del pago" />
                        <select v-model="origenPago" :disabled="!!selected" class="w-full border rounded px-2 py-2 dark:bg-gray-700 dark:border-gray-600">
                            <option value="nota">Nota de venta (productos)</option>
                            <option value="consulta">Consulta médica (servicio)</option>
                        </select>
                    </div>
                    <div v-if="origenPago === 'nota'" class="col-span-2">
                        <InputLabel value="Nota de venta" />
                        <select v-model="form.nota_venta_id" :disabled="!!selected" class="w-full border rounded px-2 py-2 dark:bg-gray-700 dark:border-gray-600">
                            <option value="">— Seleccionar —</option>
                            <option v-for="n in (selected ? notasVenta : notasParaCobro)" :key="n.id" :value="n.id">{{ notaVentaLabel(n) }}</option>
                        </select>
                    </div>
                    <div v-else class="col-span-2">
                        <InputLabel value="Consulta médica" />
                        <select v-model="form.consulta_id" :disabled="!!selected" class="w-full border rounded px-2 py-2 dark:bg-gray-700 dark:border-gray-600">
                            <option value="">— Seleccionar —</option>
                            <option v-for="c in (selected ? consultas : consultasParaCobro)" :key="c.id" :value="c.id">{{ consultaLabel(c) }}</option>
                        </select>
                    </div>
                    <div v-if="origenSeleccionado && !selected" class="col-span-2 text-sm bg-gray-50 border rounded-lg p-3">
                        Total: Bs. {{ totalOrigen(origenSeleccionado).toFixed(2) }}
                        · Pagado: Bs. {{ pagadoOrigen(origenSeleccionado).toFixed(2) }}
                        · <strong class="text-red-600">Saldo: Bs. {{ saldoOrigen(origenSeleccionado).toFixed(2) }}</strong>
                    </div>
                    <div v-if="form.tipo_pago !== 'credito' || selected">
                        <InputLabel value="Monto a cobrar (Bs.)" /><TextInput v-model="form.monto" type="number" step="0.01" class="w-full" />
                    </div>
                    <div>
                        <InputLabel value="Tipo de pago" />
                        <select v-model="form.tipo_pago" class="w-full border rounded px-2 py-2 dark:bg-gray-700 dark:border-gray-600">
                            <option v-for="(label, key) in tiposPago" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Método de pago" />
                        <select v-model="form.metodo_pago" class="w-full border rounded px-2 py-2 dark:bg-gray-700 dark:border-gray-600">
                            <option v-for="(label, key) in metodosPago" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div><InputLabel value="Fecha de pago" /><TextInput v-model="form.fecha_pago" type="datetime-local" class="w-full" /></div>
                    <div class="col-span-2"><InputLabel value="ID transacción externa" /><TextInput v-model="form.id_transaccion_externa" class="w-full" :disabled="form.metodo_pago === 'qr'" /></div>
                    <div v-if="form.metodo_pago === 'qr' && !selected" class="col-span-2">
                        <div v-if="pagoQrImage" class="flex flex-col items-center bg-emerald-50 border border-emerald-100 rounded-lg p-4">
                            <img :src="pagoQrImage" alt="QR de pago" class="max-w-[220px] rounded-lg" />
                            <p class="text-sm text-emerald-700 mt-2">
                                {{ textoEstadoVerificacionQr() }}
                            </p>
                        </div>
                        <p v-else class="text-sm text-gray-600">
                            Al guardar se generará el QR por Bs. {{ Number(form.monto || 0).toFixed(2) }}.
                        </p>
                    </div>
                    <div v-if="form.tipo_pago === 'credito' && !selected" class="col-span-2 space-y-3">
                        <div>
                            <InputLabel value="Número de pagos (inicial + cuotas)" />
                            <select
                                v-model="numPagos"
                                class="w-full border rounded px-2 py-2 dark:bg-gray-700 dark:border-gray-600"
                                @change="rebuildPlan"
                            >
                                <option v-for="n in 11" :key="n + 1" :value="n + 1">{{ n + 1 }} pagos</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">
                                El primer pago es el inicial (hoy). Los demás son cuotas con monto y fecha que usted defina (hasta 1 mes).
                            </p>
                        </div>
                        <div class="border rounded-lg overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Pago</th>
                                        <th class="px-3 py-2 text-left">Monto (Bs.)</th>
                                        <th class="px-3 py-2 text-left">Fecha</th>
                                        <th class="px-3 py-2 text-left">Saldo restante</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(pago, idx) in pagosPlan" :key="idx" class="border-t dark:border-gray-600">
                                        <td class="px-3 py-2 font-medium">{{ pago.etiqueta }}</td>
                                        <td class="px-3 py-2">
                                            <TextInput v-model="pago.monto" type="number" step="0.01" min="0.01" class="w-full" />
                                        </td>
                                        <td class="px-3 py-2">
                                            <TextInput
                                                v-if="pago.esInicial"
                                                v-model="pago.fecha"
                                                type="datetime-local"
                                                class="w-full"
                                            />
                                            <TextInput
                                                v-else
                                                v-model="pago.fecha"
                                                type="date"
                                                class="w-full"
                                                :max="maxFechaVencimiento()"
                                            />
                                        </td>
                                        <td class="px-3 py-2 text-sm text-gray-600">
                                            Bs. {{ (saldosPorFila[idx]?.restante ?? 0).toFixed(2) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p
                            class="text-sm rounded-lg p-3 border"
                            :class="planValido ? 'bg-green-50 border-green-200 text-green-800' : 'bg-amber-50 border-amber-200 text-amber-800'"
                        >
                            Suma: <strong>Bs. {{ sumaPlan.toFixed(2) }}</strong>
                            · Saldo: <strong>Bs. {{ saldoOrigen(origenSeleccionado).toFixed(2) }}</strong>
                            <span v-if="!planValido && diferencia !== 0">
                                · Falta/sobra: <strong>Bs. {{ Math.abs(diferencia).toFixed(2) }}</strong>
                            </span>
                            <span v-else-if="planValido"> · Listo para guardar</span>
                        </p>
                    </div>
                    <div v-else-if="form.tipo_pago === 'credito'" class="col-span-2">
                        <InputLabel value="Número de cuotas (del saldo restante)" />
                        <TextInput v-model="form.num_cuotas" type="number" min="2" max="36" class="w-full" />
                        <p v-if="resumenCreditoParcial" class="mt-2 text-sm text-blue-700 bg-blue-50 border border-blue-100 rounded-lg p-3">
                            Se cobrarán <strong>Bs. {{ resumenCreditoParcial.cobraHoy.toFixed(2) }}</strong> ahora
                            y quedarán <strong>Bs. {{ resumenCreditoParcial.restante.toFixed(2) }}</strong>
                            en {{ resumenCreditoParcial.cuotas }} cuotas
                            (aprox. Bs. {{ resumenCreditoParcial.porCuota.toFixed(2) }} c/u).
                        </p>
                    </div>
                </div>
                </div>
            </template>
            <template #footer>
                <FwbButton color="alternative" @click="cerrarModalPago">Cancelar</FwbButton>
                <FwbButton
                    color="green"
                    :disabled="loading || pagoQrVerificando || pagoQrEsperando"
                    @click="submit"
                >
                    {{
                        !selected && form.metodo_pago === "qr" && !pagoQrImage
                            ? "Generar QR y cobrar"
                            : !selected && form.metodo_pago === "qr" && pagoQrEsperando
                              ? "Espere 30 segundos..."
                              : !selected && form.metodo_pago === "qr" && pagoQrVerificando
                                ? "Consultando pago..."
                                : !selected && form.metodo_pago === "qr" && pagoQrImage && !showConfirmacionPago
                                  ? "Verificar pago"
                                  : "Guardar"
                    }}
                </FwbButton>
            </template>
        </FwbModal>

        <div v-if="planesPago?.length" class="mt-8">
            <h3 class="text-lg font-semibold mb-3">Planes de pago a crédito</h3>
            <div v-for="plan in planesPago" :key="plan.id" class="mb-4 p-4 border rounded-lg dark:border-gray-600">
                <p class="font-medium">Plan #{{ plan.id }} — Total Bs. {{ Number(plan.monto_total).toFixed(2) }} · Saldo pendiente Bs. {{ Number(plan.saldo_pendiente ?? plan.monto_total).toFixed(2) }} ({{ plan.cuotas_pendientes ?? plan.num_cuotas }} cuotas)</p>
                <FwbTable class="mt-2">
                    <FwbTableHead>
                        <FwbTableHeadCell>Cuota</FwbTableHeadCell>
                        <FwbTableHeadCell>Monto</FwbTableHeadCell>
                        <FwbTableHeadCell>Vencimiento</FwbTableHeadCell>
                        <FwbTableHeadCell>Estado</FwbTableHeadCell>
                        <FwbTableHeadCell>Acción</FwbTableHeadCell>
                    </FwbTableHead>
                    <FwbTableBody>
                        <FwbTableRow v-for="c in plan.cuotas" :key="c.id">
                            <FwbTableCell>{{ c.numero }}</FwbTableCell>
                            <FwbTableCell>Bs. {{ Number(c.monto).toFixed(2) }}</FwbTableCell>
                            <FwbTableCell>{{ c.fecha_vencimiento }}</FwbTableCell>
                            <FwbTableCell>{{ c.estado }}</FwbTableCell>
                            <FwbTableCell>
                                <FwbButton v-if="c.estado === 'pendiente' && canEditPagos" size="xs" @click="openPagarCuota(c)">Pagar</FwbButton>
                            </FwbTableCell>
                        </FwbTableRow>
                    </FwbTableBody>
                </FwbTable>
            </div>
        </div>

        <FwbModal v-if="isCuotaModal" @close="isCuotaModal = false">
            <template #header><h3>Pagar cuota #{{ selectedCuota?.numero }}</h3></template>
            <template #body>
                <div class="space-y-3">
                    <p class="text-sm text-gray-600">
                        Saldo de la cuota: <strong>Bs. {{ Number(selectedCuota?.monto || 0).toFixed(2) }}</strong>
                        (puede pagar un monto parcial)
                    </p>
                    <div>
                        <InputLabel value="Monto a pagar (Bs.)" />
                        <TextInput v-model="cuotaForm.monto" type="number" step="0.01" min="0.01" class="w-full" />
                    </div>
                    <div>
                        <InputLabel value="Método de pago" />
                        <select v-model="cuotaForm.metodo_pago" class="w-full border rounded px-2 py-2 dark:bg-gray-700">
                            <option v-for="(label, key) in metodosPago" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div><InputLabel value="Fecha" /><TextInput v-model="cuotaForm.fecha_pago" type="datetime-local" class="w-full" /></div>
                    <div><InputLabel value="ID transacción" /><TextInput v-model="cuotaForm.id_transaccion_externa" class="w-full" /></div>
                </div>
            </template>
            <template #footer>
                <FwbButton color="alternative" @click="isCuotaModal = false">Cancelar</FwbButton>
                <FwbButton color="green" :disabled="loading" @click="submitCuota">Registrar pago</FwbButton>
            </template>
        </FwbModal>

        <FwbModal v-if="isDeleteModal" @close="isDeleteModal = false">
            <template #header><h3>Confirmar eliminación</h3></template>
            <template #body>¿Eliminar este pago de Bs. {{ selected?.monto }}?</template>
            <template #footer>
                <FwbButton color="alternative" @click="isDeleteModal = false">Cancelar</FwbButton>
                <FwbButton color="red" :disabled="loading" @click="confirmDelete">Eliminar</FwbButton>
            </template>
        </FwbModal>

        <PagoQrConfirmacionModal
            :show="showConfirmacionPago"
            :info="pagoQrInfo"
            :loading="loading"
            @confirmar="confirmarPagoQrYGuardar"
        />
    </AdminLayout>
</template>

<style scoped>
.modal-pago-cuerpo {
    max-height: min(82vh, 920px);
    overflow-y: auto;
    padding-right: 0.25rem;
}
</style>
