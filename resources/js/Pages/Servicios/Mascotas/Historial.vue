<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link } from "@inertiajs/vue3";
import { FwbBadge, FwbButton } from "flowbite-vue";

const props = defineProps({
    mascota: Object,
    eventos: Array,
    resumen: Object,
});

const tipoIcono = {
    consulta: "fa-stethoscope",
    vacuna: "fa-syringe",
    tratamiento: "fa-pills",
    pago: "fa-money-bill",
};

const tipoColor = {
    consulta: "blue",
    vacuna: "green",
    tratamiento: "teal",
    pago: "yellow",
};

const estadoBadge = {
    reservada: "yellow",
    en_atencion: "blue",
    completada: "green",
    cancelada: "red",
    no_asistio: "dark",
};

function formatearFecha(fecha) {
    if (!fecha) return "—";
    return new Date(fecha).toLocaleString("es-BO");
}
</script>

<template>
    <AdminLayout :title="`Historial — ${mascota.name}`">
        <div class="mb-6 flex flex-wrap justify-between items-start gap-4">
            <div>
                <Link :href="route('mascotas.index')" class="text-sm text-emerald-600 hover:text-emerald-800 hover:underline mb-2 inline-block">
                    ← Volver a mascotas
                </Link>
                <h2 class="text-2xl font-semibold vet-page-title">Historial clínico — {{ mascota.name }}</h2>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ mascota.owner?.first_name }} {{ mascota.owner?.last_name }}
                    · {{ mascota.breed?.specie?.name }} — {{ mascota.breed?.name }}
                </p>
            </div>
            <FwbButton
                tag="a"
                :href="route('consultas-medicas.historial-reporte', { pet: mascota.id })"
                target="_blank"
                color="green"
            >
                <i class="fa-solid fa-file-pdf mr-2"></i> Descargar PDF
            </FwbButton>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-8">
            <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg text-center">
                <p class="text-2xl font-bold">{{ resumen.total_consultas }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">Consultas</p>
            </div>
            <div class="p-3 bg-green-50 dark:bg-green-900/30 rounded-lg text-center">
                <p class="text-2xl font-bold">{{ resumen.consultas_completadas }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">Completadas</p>
            </div>
            <div class="p-3 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg text-center">
                <p class="text-2xl font-bold">{{ resumen.consultas_reservadas }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">Reservadas</p>
            </div>
            <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg text-center">
                <p class="text-2xl font-bold">{{ resumen.total_vacunas }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">Vacunas</p>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-center">
                <p class="text-2xl font-bold">{{ resumen.total_pagos }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">Pagos</p>
            </div>
        </div>

        <div v-if="!eventos.length" class="text-center py-12 text-gray-500">
            Esta mascota aún no tiene eventos clínicos registrados.
        </div>

        <ol v-else class="relative border-s border-gray-300 dark:border-gray-600 ms-4">
            <li v-for="(evento, idx) in eventos" :key="idx" class="mb-8 ms-6">
                <span
                    class="absolute flex items-center justify-center w-8 h-8 rounded-full -start-4 ring-4 ring-white dark:ring-gray-900"
                    :class="{
                        'bg-blue-100 text-blue-700': evento.tipo === 'consulta',
                        'bg-green-100 text-green-700': evento.tipo === 'vacuna',
                        'bg-emerald-100 text-emerald-700': evento.tipo === 'tratamiento',
                        'bg-yellow-100 text-yellow-700': evento.tipo === 'pago',
                    }"
                >
                    <i class="fa-solid text-sm" :class="tipoIcono[evento.tipo]"></i>
                </span>
                <div class="p-4 bg-white dark:bg-gray-800 border rounded-lg shadow-sm">
                    <div class="flex flex-wrap justify-between gap-2 mb-2">
                        <h3 class="font-semibold">{{ evento.titulo }}</h3>
                        <span class="text-sm text-gray-500">{{ formatearFecha(evento.fecha) }}</span>
                    </div>
                    <FwbBadge v-if="evento.estado_label" :type="estadoBadge[evento.estado] || 'dark'" class="mb-2">
                        {{ evento.estado_label }}
                    </FwbBadge>

                    <template v-if="evento.tipo === 'consulta'">
                        <p v-if="evento.motivo"><strong>Motivo:</strong> {{ evento.motivo }}</p>
                        <p v-if="evento.diagnostico"><strong>Diagnóstico:</strong> {{ evento.diagnostico }}</p>
                        <p v-if="evento.veterinario"><strong>Veterinario:</strong> {{ evento.veterinario }}</p>
                        <p v-if="evento.costo"><strong>Costo:</strong> Bs. {{ Number(evento.costo).toFixed(2) }}</p>
                        <ul v-if="evento.tratamientos?.length" class="mt-2 text-sm list-disc ms-4">
                            <li v-for="(t, i) in evento.tratamientos" :key="i">
                                {{ t.producto }} (x{{ t.cantidad }}) — {{ t.instrucciones }}
                            </li>
                        </ul>
                    </template>

                    <template v-else-if="evento.tipo === 'vacuna'">
                        <p v-if="evento.proxima"><strong>Próxima:</strong> {{ evento.proxima }}</p>
                        <p v-if="evento.veterinario"><strong>Aplicado por:</strong> {{ evento.veterinario }}</p>
                        <p v-if="evento.notas">{{ evento.notas }}</p>
                    </template>

                    <template v-else-if="evento.tipo === 'tratamiento'">
                        <p>Cantidad: {{ evento.cantidad }} — {{ evento.instrucciones }}</p>
                        <p v-if="evento.notas">{{ evento.notas }}</p>
                    </template>

                    <template v-else-if="evento.tipo === 'pago'">
                        <p><strong>Monto:</strong> Bs. {{ Number(evento.monto).toFixed(2) }}</p>
                        <p>{{ evento.tipo_pago }} — {{ evento.metodo_pago }}</p>
                    </template>
                </div>
            </li>
        </ol>
    </AdminLayout>
</template>
