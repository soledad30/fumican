<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";

defineProps({ mensaje: String });
</script>

<template>
    <AdminLayout title="Mi cuenta">
        <div class="vet-panel p-8 text-center max-w-lg mx-auto mt-12">
            <i class="fa-solid fa-link-slash text-4xl text-amber-500 mb-4"></i>
            <p class="text-lg">{{ mensaje }}</p>
        </div>
    </AdminLayout>
</template>
