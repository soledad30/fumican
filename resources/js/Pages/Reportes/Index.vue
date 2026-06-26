<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { FwbButton } from "flowbite-vue";
import { usePermisos } from "@/Composables/usePermisos";

const props = defineProps({
    estadisticasNegocio: Object,
    estadisticasAcceso: Object,
    visitasTop: Array,
    visitasTotal: Number,
});

const { puede, esAdmin } = usePermisos();
const canVerReportes = computed(() => puede("ver bitacora") || puede("listar bitacora"));
const canVerMatriz = computed(() => esAdmin.value || puede("administrar_sistema"));

const maxVisitas = computed(() => {
    const tops = props.visitasTop ?? [];
    return Math.max(...tops.map((v) => v.contador), 1);
});

function barPct(contador) {
    return Math.round((contador / maxVisitas.value) * 100);
}

function formatRuta(ruta) {
    const clean = (ruta || "").replace(/^\//, "").replace(/\//g, " › ");
    return clean || ruta || "—";
}

function visitasLabel(n) {
    return n === 1 ? "1 visita" : `${n} visitas`;
}
</script>

<template>
    <AdminLayout title="Reportes y estadísticas">
        <div class="vet-section-header">
            <div>
                <h1 class="text-2xl font-bold vet-page-title">Reportes y estadísticas</h1>
                <p class="vet-page-subtitle mt-1">Resumen del negocio y actividad del sistema</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Link v-if="canVerReportes" :href="route('reportes.bitacora')">
                    <FwbButton color="green" size="sm">
                        <i class="fa-solid fa-book mr-2"></i> Bitácora
                    </FwbButton>
                </Link>
                <Link v-if="canVerMatriz" :href="route('reportes.matriz-acceso')">
                    <FwbButton color="alternative" size="sm">
                        <i class="fa-solid fa-table-cells mr-2"></i> Matriz de acceso
                    </FwbButton>
                </Link>
            </div>
        </div>

        <!-- KPIs principales -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="vet-stat-card">
                <div class="vet-stat-card__header">
                    <span class="vet-stat-card__label">Clientes</span>
                    <span class="vet-stat-card__icon"><i class="fa-solid fa-users"></i></span>
                </div>
                <p class="vet-stat-card__value">{{ estadisticasNegocio?.total_clientes ?? 0 }}</p>
            </div>
            <div class="vet-stat-card">
                <div class="vet-stat-card__header">
                    <span class="vet-stat-card__label">Mascotas</span>
                    <span class="vet-stat-card__icon"><i class="fa-solid fa-paw"></i></span>
                </div>
                <p class="vet-stat-card__value">{{ estadisticasNegocio?.total_mascotas ?? 0 }}</p>
            </div>
            <div class="vet-stat-card">
                <div class="vet-stat-card__header">
                    <span class="vet-stat-card__label">Consultas</span>
                    <span class="vet-stat-card__icon"><i class="fa-solid fa-stethoscope"></i></span>
                </div>
                <p class="vet-stat-card__value">{{ estadisticasNegocio?.total_consultas ?? 0 }}</p>
            </div>
            <div class="vet-stat-card">
                <div class="vet-stat-card__header">
                    <span class="vet-stat-card__label">Productos</span>
                    <span class="vet-stat-card__icon"><i class="fa-solid fa-pills"></i></span>
                </div>
                <p class="vet-stat-card__value">{{ estadisticasNegocio?.total_productos ?? 0 }}</p>
            </div>
        </div>

        <!-- Paneles de detalle -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="vet-panel">
                <h2 class="vet-panel__title">
                    <i class="fa-solid fa-right-to-bracket"></i> Acceso al sistema
                </h2>
                <div class="vet-panel__row">
                    <span>Inicios de sesión exitosos</span>
                    <strong>{{ estadisticasAcceso?.logins_exitosos ?? 0 }}</strong>
                </div>
                <div class="vet-panel__row">
                    <span>Intentos fallidos</span>
                    <strong>{{ estadisticasAcceso?.logins_fallidos ?? 0 }}</strong>
                </div>
                <div class="vet-panel__row">
                    <span>Visitas totales al sitio</span>
                    <strong>{{ visitasTotal ?? 0 }}</strong>
                </div>
            </div>

            <div class="vet-panel">
                <h2 class="vet-panel__title">
                    <i class="fa-solid fa-money-bill-wave"></i> Pagos
                </h2>
                <div class="vet-panel__row">
                    <span>Total contado</span>
                    <span class="vet-panel__value">Bs. {{ estadisticasNegocio?.pagos?.total_contado ?? 0 }}</span>
                </div>
                <div class="vet-panel__row">
                    <span>Total crédito</span>
                    <span class="vet-panel__value">Bs. {{ estadisticasNegocio?.pagos?.total_credito ?? 0 }}</span>
                </div>
                <div class="vet-panel__row">
                    <span>Pendientes</span>
                    <strong>{{ estadisticasNegocio?.pagos?.pendientes ?? 0 }}</strong>
                </div>
            </div>
        </div>

        <!-- Páginas más visitadas -->
        <div class="vet-panel vet-panel--visits">
            <div class="vet-panel__title-row">
                <h2 class="vet-panel__title vet-panel__title--inline">
                    <i class="fa-solid fa-chart-line"></i> Páginas más visitadas
                </h2>
                <span v-if="visitasTop?.length" class="vet-visit-summary">
                    Top {{ visitasTop.length }} · {{ visitasTotal ?? 0 }} visitas totales
                </span>
            </div>
            <ul v-if="visitasTop?.length" class="vet-visit-rank">
                <li v-for="(v, index) in visitasTop" :key="v.ruta">
                    <span
                        class="vet-visit-rank__pos"
                        :class="{ 'vet-visit-rank__pos--top': index < 3 }"
                    >{{ index + 1 }}</span>
                    <div class="vet-visit-rank__body">
                        <div class="vet-visit-rank__header">
                            <div class="vet-visit-rank__labels">
                                <span class="vet-visit-rank__route">{{ formatRuta(v.ruta) }}</span>
                                <span class="vet-visit-rank__path" :title="v.ruta">{{ v.ruta }}</span>
                            </div>
                            <span class="vet-visit-rank__count">{{ visitasLabel(v.contador) }}</span>
                        </div>
                        <div class="vet-visit-rank__bar" role="presentation">
                            <div
                                class="vet-visit-rank__bar-fill"
                                :style="{ width: `${barPct(v.contador)}%` }"
                            ></div>
                        </div>
                    </div>
                </li>
            </ul>
            <p v-else class="vet-visit-empty">Sin datos de visitas registradas.</p>
        </div>
    </AdminLayout>
</template>
