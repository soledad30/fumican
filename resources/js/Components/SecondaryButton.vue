<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center px-4 py-2 bg-white border border-emerald-200 rounded-lg font-semibold text-xs text-emerald-800 uppercase tracking-widest shadow-sm hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
        <slot />
    </button>
</template>
