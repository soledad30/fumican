<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <div class="mb-4">
        <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-200">
            {{ title }}
        </h3>
    </div>
</template>
