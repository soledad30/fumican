<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useTheme } from '@/Composables/useTheme';

const { theme, setTheme, getTimeVariant } = useTheme();
const isMenuOpen = ref(false);
const dropdownRef = ref(null);

const themes = [
    { name: 'ninos', label: 'Niños', icon: 'fa-child', hint: 'Pasteles suaves' },
    { name: 'jovenes', label: 'Jóvenes', icon: 'fa-user-graduate', hint: 'Naranja moderno' },
    { name: 'adultos', label: 'Adultos', icon: 'fa-user-tie', hint: 'Verde veterinario' },
];

const timeLabel = computed(() => (getTimeVariant() === 'day' ? 'Día' : 'Noche'));
const activeLabel = computed(() => themes.find((t) => t.name === theme.value)?.label ?? 'Adultos');

const selectTheme = (newTheme) => {
    setTheme(newTheme);
    isMenuOpen.value = false;
};

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isMenuOpen.value = false;
    }
};

onMounted(() => document.addEventListener('mousedown', handleClickOutside));
onBeforeUnmount(() => document.removeEventListener('mousedown', handleClickOutside));
</script>

<template>
    <div class="relative" ref="dropdownRef">
        <button
            type="button"
            class="theme-trigger"
            :title="`Tema: ${activeLabel} (${timeLabel} automático)`"
            aria-label="Elegir tema visual"
            aria-haspopup="true"
            :aria-expanded="isMenuOpen"
            @click="isMenuOpen = !isMenuOpen"
        >
            <i class="fa-solid fa-palette"></i>
        </button>

        <transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-if="isMenuOpen"
                class="theme-menu"
                role="menu"
            >
                <p class="theme-menu__hint">
                    <i class="fa-solid fa-clock mr-1"></i>
                    Modo {{ timeLabel }} según tu hora local
                </p>
                <button
                    v-for="t in themes"
                    :key="t.name"
                    type="button"
                    role="menuitem"
                    class="theme-menu__item"
                    :class="{ 'theme-menu__item--active': theme === t.name }"
                    @click="selectTheme(t.name)"
                >
                    <i class="fa-solid w-5" :class="t.icon"></i>
                    <span>
                        <span class="block font-semibold">{{ t.label }}</span>
                        <span class="block text-xs opacity-70">{{ t.hint }}</span>
                    </span>
                    <i v-if="theme === t.name" class="fa-solid fa-check ml-auto text-emerald-600"></i>
                </button>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.theme-trigger {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.375rem;
    color: var(--color-header-text);
    border: 1px solid color-mix(in srgb, var(--color-border) 40%, transparent);
    background: color-mix(in srgb, var(--color-header-from) 35%, transparent);
    transition: background 0.15s;
}

.theme-trigger:hover {
    background: color-mix(in srgb, var(--color-header-from) 65%, transparent);
    border-color: var(--color-border);
}

.theme-menu {
    position: absolute;
    right: 0;
    z-index: 50;
    margin-top: 0.5rem;
    width: 13rem;
    padding: 0.5rem;
    background: #ffffff;
    border: 1px solid #d1fae5;
    border-radius: 0.75rem;
    box-shadow: 0 10px 25px rgba(6, 78, 59, 0.15);
}

.theme-menu__hint {
    font-size: 0.7rem;
    color: #5f7a76;
    padding: 0.35rem 0.5rem 0.5rem;
    border-bottom: 1px solid #ecfdf5;
    margin-bottom: 0.25rem;
}

.theme-menu__item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    text-align: left;
    padding: 0.5rem 0.625rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    color: #134e4a;
    transition: background 0.12s;
}

.theme-menu__item:hover {
    background: #f0fdf9;
}

.theme-menu__item--active {
    background: #ecfdf5;
    color: #047857;
}
</style>
