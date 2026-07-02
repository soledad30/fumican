<script setup>
import { FwbModal, FwbButton, FwbBadge } from "flowbite-vue";
import { computed } from "vue";
import { formatearFechaHoraPagoQr, textoEstadoPagoQr, infoPagoQrConfirmada } from "@/Composables/usePagoQr";

const props = defineProps({
    show: { type: Boolean, default: false },
    info: { type: Object, default: null },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(["confirmar"]);

const estadoBadge = computed(() => {
    if (infoPagoQrConfirmada(props.info)) return "green";
    const status = props.info?.paymentStatus;
    if (status === 2) return "yellow";
    return "red";
});

const estadoTexto = computed(() => {
    return textoEstadoPagoQr(
        props.info?.paymentStatus,
        props.info?.paymentStatusDescription
    );
});
</script>

<template>
    <FwbModal v-if="show" size="2xl" @close="emit('confirmar')">
        <template #header>
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-500"></i>
                <span>Pago QR confirmado</span>
            </div>
        </template>
        <template #body>
            <div v-if="info" class="space-y-4">
                <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-4 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm text-emerald-800">Estado del pago</p>
                        <p class="font-semibold text-emerald-900">{{ estadoTexto }}</p>
                    </div>
                    <FwbBadge :type="estadoBadge">{{ estadoTexto }}</FwbBadge>
                </div>

                <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
                    <h4 class="font-semibold mb-3 text-gray-800 dark:text-gray-100">Transacción</h4>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                        <div v-if="info.pagofacilTransactionId">
                            <dt class="text-gray-500">ID PagoFácil</dt>
                            <dd class="font-medium break-all">{{ info.pagofacilTransactionId }}</dd>
                        </div>
                        <div v-if="info.companyTransactionId">
                            <dt class="text-gray-500">Referencia interna</dt>
                            <dd class="font-medium break-all">{{ info.companyTransactionId }}</dd>
                        </div>
                        <div v-if="info.amount != null">
                            <dt class="text-gray-500">Monto pagado</dt>
                            <dd class="font-semibold text-emerald-700">
                                {{ info.currencyCode || "BOB" }} {{ Number(info.amount).toFixed(2) }}
                            </dd>
                        </div>
                        <div v-if="info.paymentMethodDetail">
                            <dt class="text-gray-500">Método</dt>
                            <dd class="font-medium">{{ info.paymentMethodDetail }}</dd>
                        </div>
                        <div v-if="info.paymentDate">
                            <dt class="text-gray-500">Fecha de pago</dt>
                            <dd class="font-medium">
                                {{ formatearFechaHoraPagoQr(info.paymentDate, info.paymentTime) }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div
                    v-if="info.payerName || info.payerDocument || info.payerAccount || info.payerBank"
                    class="rounded-lg bg-blue-50 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900 p-4"
                >
                    <h4 class="font-semibold mb-3 text-gray-800 dark:text-gray-100">
                        <i class="fa-solid fa-user-check mr-1 text-blue-600"></i>
                        Quién realizó el pago
                    </h4>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                        <div v-if="info.payerName">
                            <dt class="text-gray-500">Nombre</dt>
                            <dd class="font-medium">{{ info.payerName }}</dd>
                        </div>
                        <div v-if="info.payerDocument">
                            <dt class="text-gray-500">Documento / CI</dt>
                            <dd class="font-medium">{{ info.payerDocument }}</dd>
                        </div>
                        <div v-if="info.payerAccount">
                            <dt class="text-gray-500">Cuenta</dt>
                            <dd class="font-medium break-all">{{ info.payerAccount }}</dd>
                        </div>
                        <div v-if="info.payerBank">
                            <dt class="text-gray-500">Banco</dt>
                            <dd class="font-medium">{{ info.payerBank }}</dd>
                        </div>
                    </dl>
                </div>

                <p class="text-sm text-gray-500">
                    Revise los datos del pagador. Al cerrar, el pago quedará registrado en el sistema.
                </p>
            </div>
        </template>
        <template #footer>
            <div class="flex justify-end w-full">
                <FwbButton color="green" :loading="loading" @click="emit('confirmar')">
                    Cerrar y registrar pago
                </FwbButton>
            </div>
        </template>
    </FwbModal>
</template>
