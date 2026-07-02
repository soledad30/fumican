<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link } from "@inertiajs/vue3";
import { FwbBadge, FwbButton } from "flowbite-vue";
import { formatearFechaHora } from "@/Utils/fechaBolivia";

defineProps({
    mascota: Object,
    resumen: Object,
    vacunas: Array,
    proximas_citas: Array,
    eventos_recientes: Array,
    alertas: Array,
});

const estadoBadge = {
    vencido: "red",
    proximo: "yellow",
    al_dia: "green",
    sin_programar: "dark",
    pendiente: "dark",
};

function formatearFecha(f) {
    return formatearFechaHora(f);
}
</script>

<template>
    <AdminLayout :title="`Carnet — ${mascota.name}`">
        <div class="mb-6 flex flex-wrap justify-between gap-4">
            <div>
                <Link :href="route('portal.index')" class="text-sm text-emerald-600 hover:underline">
                    ← Mis mascotas
                </Link>
                <h2 class="text-2xl font-semibold vet-page-title mt-1">
                    Carnet — {{ mascota.name }}
                </h2>
                <p class="text-sm text-gray-500">
                    {{ mascota.breed?.specie?.name }} · {{ mascota.breed?.name }}
                </p>
            </div>
            <FwbButton
                tag="a"
                :href="route('portal.carnet.pdf', mascota.id)"
                target="_blank"
                color="alternative"
            >
                <i class="fa-solid fa-file-pdf mr-2"></i> Descargar PDF
            </FwbButton>
        </div>

        <div v-if="alertas?.length" class="mb-6 space-y-2">
            <div
                v-for="(a, i) in alertas"
                :key="i"
                class="p-3 rounded-lg text-sm border"
                :class="{
                    'bg-red-50 border-red-200 text-red-800': a.nivel === 'danger',
                    'bg-amber-50 border-amber-200 text-amber-900': a.nivel === 'warning',
                    'bg-blue-50 border-blue-200 text-blue-900': a.nivel === 'info',
                }"
            >
                <i class="fa-solid fa-circle-info mr-2"></i>{{ a.mensaje }}
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-8">
            <div class="vet-panel p-3 text-center">
                <p class="text-2xl font-bold">{{ resumen.total_consultas }}</p>
                <p class="text-xs text-gray-500">Consultas</p>
            </div>
            <div class="vet-panel p-3 text-center">
                <p class="text-2xl font-bold">{{ resumen.total_vacunas }}</p>
                <p class="text-xs text-gray-500">Vacunas</p>
            </div>
            <div class="vet-panel p-3 text-center">
                <p class="text-2xl font-bold">{{ proximas_citas?.length ?? 0 }}</p>
                <p class="text-xs text-gray-500">Citas próximas</p>
            </div>
            <div class="vet-panel p-3 text-center">
                <p class="text-2xl font-bold">{{ mascota.weight ?? mascota.peso ?? "—" }}</p>
                <p class="text-xs text-gray-500">Peso (kg)</p>
            </div>
        </div>

        <section class="vet-panel p-4 mb-6">
            <h3 class="font-semibold mb-3">
                <i class="fa-solid fa-syringe mr-2 text-emerald-600"></i>Vacunas y recordatorios
            </h3>
            <div v-if="!vacunas?.length" class="text-sm text-gray-500">Sin registros de vacunación.</div>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500">
                            <th class="py-2">Vacuna</th>
                            <th>Última</th>
                            <th>Próxima</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(v, i) in vacunas" :key="i" class="border-b border-gray-100">
                            <td class="py-2 font-medium">{{ v.vacuna }}</td>
                            <td>{{ v.ultima_aplicacion ?? "—" }}</td>
                            <td>{{ v.proxima ?? "—" }}</td>
                            <td>
                                <FwbBadge :type="estadoBadge[v.estado] ?? 'dark'">
                                    {{ v.estado_label }}
                                </FwbBadge>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section v-if="proximas_citas?.length" class="vet-panel p-4 mb-6">
            <h3 class="font-semibold mb-3">
                <i class="fa-solid fa-calendar mr-2 text-blue-600"></i>Próximas citas
            </h3>
            <ul class="space-y-2 text-sm">
                <li v-for="c in proximas_citas" :key="c.id">
                    <strong>{{ c.fecha }}</strong>
                    <span v-if="c.hora"> {{ c.hora }}</span>
                    — {{ c.servicio ?? c.motivo ?? "Consulta" }}
                </li>
            </ul>
        </section>

        <section class="vet-panel p-4">
            <h3 class="font-semibold mb-3">Actividad reciente</h3>
            <ul class="space-y-3 text-sm">
                <li v-for="(e, i) in eventos_recientes" :key="i" class="border-b pb-2">
                    <span class="font-medium">{{ e.titulo }}</span>
                    <span class="text-gray-500 ml-2">{{ formatearFecha(e.fecha) }}</span>
                </li>
            </ul>
        </section>
    </AdminLayout>
</template>
