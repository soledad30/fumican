<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({ status: Number });

const title = computed(() => ({
    503: "503: Servicio no disponible",
    500: "500: Error del servidor",
    404: "404: Página no encontrada",
    403: "403: Sin autorización",
}[props.status]));

const description = computed(() => ({
    503: "Estamos realizando mantenimiento. Vuelve pronto.",
    500: "Algo salió mal en nuestros servidores.",
    404: "La página que buscas no existe o fue movida.",
    403: "No tienes permiso para acceder a esta sección.",
}[props.status]));
</script>

<template>
    <Head title="Error" />
    <div class="min-h-screen bg-gradient-to-b from-emerald-50 to-white flex flex-col items-center justify-center px-6 py-16">
        <div class="w-20 h-20 rounded-2xl bg-emerald-100 flex items-center justify-center mb-6">
            <i class="fa-solid fa-paw text-3xl text-emerald-600"></i>
        </div>
        <h1 class="text-5xl sm:text-6xl font-bold text-emerald-900 mb-3 text-center">{{ title }}</h1>
        <p class="text-emerald-700/80 text-center max-w-md mb-8">{{ description }}</p>
        <Link
            href="/"
            class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition shadow-lg"
        >
            <i class="fa-solid fa-house"></i>
            Volver al inicio
        </Link>
    </div>
</template>
