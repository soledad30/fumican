<script setup>
import { ref, onMounted } from 'vue';
import { useAccessibility } from '@/Composables/useAccessibility';

const { aplicar, aumentarLetra: up, reducirLetra: down, toggleContraste: toggle, getFontScale, isAltoContraste } = useAccessibility();
const fontScale = ref(getFontScale());
const altoContraste = ref(isAltoContraste());

defineProps({
    /** 'header' = botones claros sobre barra; 'default' = panel claro */
    variant: {
        type: String,
        default: 'header',
    },
});

const aumentarLetra = () => {
    fontScale.value = up();
};

const reducirLetra = () => {
    fontScale.value = down();
};

const toggleContraste = () => {
    altoContraste.value = toggle();
};

onMounted(() => {
    aplicar();
    fontScale.value = getFontScale();
    altoContraste.value = isAltoContraste();
});
</script>

<template>
    <div
        class="flex items-center gap-1"
        role="group"
        aria-label="Accesibilidad: tamaño de letra y contraste"
    >
        <button
            type="button"
            class="access-btn"
            :class="variant === 'header' ? 'access-btn--header' : 'access-btn--default'"
            title="Reducir tamaño de letra"
            aria-label="Reducir tamaño de letra"
            @click="reducirLetra"
        >A−</button>
        <button
            type="button"
            class="access-btn"
            :class="variant === 'header' ? 'access-btn--header' : 'access-btn--default'"
            title="Aumentar tamaño de letra"
            aria-label="Aumentar tamaño de letra"
            @click="aumentarLetra"
        >A+</button>
        <button
            type="button"
            class="access-btn"
            :class="[
                variant === 'header' ? 'access-btn--header' : 'access-btn--default',
                { 'access-btn--active': altoContraste },
            ]"
            title="Alternar alto contraste"
            aria-label="Alto contraste"
            :aria-pressed="altoContraste"
            @click="toggleContraste"
        >◐</button>
    </div>
</template>

<style scoped>
.access-btn {
    min-width: 2rem;
    padding: 0.25rem 0.4rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1;
    cursor: pointer;
    transition: background 0.15s, color 0.15s, border-color 0.15s;
}

.access-btn--header {
    border: 1px solid color-mix(in srgb, var(--color-border) 55%, transparent);
    background: color-mix(in srgb, var(--color-header-from) 35%, transparent);
    color: var(--color-header-text);
}

.access-btn--header:hover {
    background: color-mix(in srgb, var(--color-header-from) 65%, transparent);
    border-color: var(--color-border);
}

.access-btn--header.access-btn--active {
    background: var(--color-header-text);
    color: var(--color-header-from);
    border-color: var(--color-header-text);
}

.access-btn--default {
    border: 1px solid #a7f3d0;
    background: #ffffff;
    color: #065f46;
}

.access-btn--default:hover {
    background: #ecfdf5;
}

.access-btn--default.access-btn--active {
    background: #d1fae5;
    border-color: #059669;
}
</style>
