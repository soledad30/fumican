<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { computed } from "vue";
import VueApexCharts from "vue3-apexcharts";
import { mergeChartOptions } from "@/Composables/useChartTheme";

const props = defineProps({
    stats: Object,
});

const consultationsChart = computed(() => ({
    series: [{ name: "Consultas", data: props.stats.consultations.data }],
    options: mergeChartOptions({
        chart: { type: "area", height: 350, zoom: { enabled: false }, toolbar: { show: false } },
        dataLabels: { enabled: false },
        stroke: { curve: "smooth" },
        xaxis: { categories: props.stats.consultations.labels },
        yaxis: { title: { text: "Nº de Consultas" } },
        title: { text: "Rendimiento de Consultas (Últimos 12 Meses)", align: "left" },
        grid: { row: { colors: ["#f0fdf9", "transparent"], opacity: 0.6 } },
        tooltip: { y: { formatter: (val) => `${val} consultas` } },
    }),
}));

const speciesChart = computed(() => ({
    series: props.stats.species.data,
    options: mergeChartOptions({
        chart: { type: "donut", height: 350 },
        labels: props.stats.species.labels,
        title: { text: "Distribución de Pacientes por Especie", align: "left" },
        responsive: [{ breakpoint: 480, options: { chart: { width: 200 }, legend: { position: "bottom" } } }],
        legend: { position: "right", offsetY: 0, height: 230 },
    }),
}));

const newCustomersChart = computed(() => ({
    series: [{ name: "Nuevos Clientes", data: props.stats.newCustomers.data }],
    options: mergeChartOptions({
        chart: { type: "bar", height: 350, toolbar: { show: false } },
        plotOptions: { bar: { borderRadius: 4, horizontal: false, columnWidth: "50%" } },
        dataLabels: { enabled: false },
        xaxis: { categories: props.stats.newCustomers.labels },
        yaxis: { title: { text: "Nº de Clientes" } },
        title: { text: "Adquisición de Clientes (Últimos 6 Meses)", align: "left" },
        tooltip: { y: { formatter: (val) => `${val} clientes` } },
    }),
}));

const salesVsPurchasesChart = computed(() => ({
    series: [
        { name: "Ventas Totales", data: props.stats.salesVsPurchases.sales },
        { name: "Compras Totales", data: props.stats.salesVsPurchases.purchases },
    ],
    options: mergeChartOptions({
        chart: { type: "line", height: 350, toolbar: { show: false } },
        stroke: { width: [4, 4], curve: "smooth" },
        title: { text: "Análisis Financiero - Ventas vs Compras (Últimos 6 Meses)", align: "left" },
        xaxis: { categories: props.stats.salesVsPurchases.labels },
        yaxis: [
            { seriesName: "Ventas Totales", title: { text: "Monto en Bs." }, axisTicks: { show: true }, axisBorder: { show: true } },
            { seriesName: "Compras Totales", show: false },
        ],
        tooltip: { y: { formatter: (val) => `Bs. ${val.toFixed(2)}` } },
    }),
}));

const topMedicamentsChart = computed(() => ({
    series: [{ name: "Cantidad Vendida", data: props.stats.topMedicaments.data }],
    options: mergeChartOptions({
        chart: { type: "bar", height: 350, toolbar: { show: false } },
        plotOptions: { bar: { borderRadius: 4, horizontal: true } },
        dataLabels: { enabled: false },
        xaxis: { categories: props.stats.topMedicaments.labels },
        title: { text: "Top 5 Medicamentos Más Vendidos", align: "left" },
        tooltip: { x: { formatter: (val) => val }, y: { formatter: (val) => `${val} unidades` } },
    }),
}));

const inventoryValueChart = computed(() => ({
    series: props.stats.inventoryValue.data,
    options: mergeChartOptions({
        chart: { type: "pie", height: 350 },
        labels: props.stats.inventoryValue.labels,
        title: { text: "Valor del Inventario por Almacén", align: "left" },
        responsive: [{ breakpoint: 480, options: { chart: { width: 200 }, legend: { position: "bottom" } } }],
        legend: { position: "bottom" },
        tooltip: { y: { formatter: (val) => `Bs. ${val.toFixed(2)}` } },
    }),
}));
</script>

<template>
    <AdminLayout title="Dashboard">
        <div class="flex justify-between my-6 items-center">
            <h2 class="text-2xl font-semibold vet-page-title">
                Dashboard Analítico
            </h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="vet-card vet-chart-card p-4">
                <VueApexCharts type="bar" height="350" :options="topMedicamentsChart.options" :series="topMedicamentsChart.series" />
            </div>
            <div class="vet-card vet-chart-card p-4">
                <VueApexCharts type="donut" height="350" :options="speciesChart.options" :series="speciesChart.series" />
            </div>
            <div class="vet-card vet-chart-card p-4 lg:col-span-2">
                <VueApexCharts type="line" height="350" :options="salesVsPurchasesChart.options" :series="salesVsPurchasesChart.series" />
            </div>
            <div class="vet-card vet-chart-card p-4">
                <VueApexCharts type="area" height="350" :options="consultationsChart.options" :series="consultationsChart.series" />
            </div>
            <div class="vet-card vet-chart-card p-4">
                <VueApexCharts type="bar" height="350" :options="newCustomersChart.options" :series="newCustomersChart.series" />
            </div>
            <div class="vet-card vet-chart-card p-4">
                <VueApexCharts type="pie" height="350" :options="inventoryValueChart.options" :series="inventoryValueChart.series" />
            </div>
        </div>
    </AdminLayout>
</template>
