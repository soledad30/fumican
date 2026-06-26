<template>
    <footer class="vet-footer border-t">
        <div class="container px-6 mx-auto grid">
            <p class="text-center py-4 text-sm">
                © 2026 Vetfumican — Grupo 23SA
            </p>
            <p class="text-center pb-4 text-sm">
                Visitas a esta página: <strong>{{ visitCount }}</strong>
            </p>
        </div>
    </footer>
</template>

<script setup>
defineProps({
    visitCount: {
        type: Number,
        required: true,
    },
});
</script>
