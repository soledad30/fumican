<script setup>
import { computed } from 'vue';

const props = defineProps({
    message: {
        type: [String, Array],
        default: '',
    },
});

const text = computed(() => {
    if (Array.isArray(props.message)) {
        return props.message[0] ?? '';
    }

    return props.message ?? '';
});
</script>

<template>
    <p v-if="text" class="mt-1 text-sm font-medium !text-red-600 dark:!text-red-400">
        {{ text }}
    </p>
</template>
