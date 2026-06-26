<script setup>
import { FwbAvatar } from "flowbite-vue";
import { Link, router } from "@inertiajs/vue3";
import ThemeSwitcher from '@/Components/ThemeSwitcher.vue';
import AccessibilityPanel from '@/Components/AccessibilityPanel.vue';
import { ref, watch, onMounted, onBeforeUnmount } from "vue";
import axios from "axios";
import { useDebouncedRef } from "@/Utils/debouncedRef.js";

defineProps({
    toggleSideMenu: Function,
    user: Object,
});

const logout = () => {
    router.post(route("logout"));
};

const userMenuOpen = ref(false);
const userMenuRef = ref(null);

const closeUserMenu = () => {
    userMenuOpen.value = false;
};

const handleUserMenuOutside = (event) => {
    if (userMenuRef.value && !userMenuRef.value.contains(event.target)) {
        closeUserMenu();
    }
};

onMounted(() => document.addEventListener('mousedown', handleUserMenuOutside));
onBeforeUnmount(() => document.removeEventListener('mousedown', handleUserMenuOutside));

// --- Lógica de Búsqueda Global ---
const searchTerm = useDebouncedRef('', 300); // 300ms de espera antes de buscar
const searchResults = ref([]);
const isSearching = ref(false);
const showResultsDropdown = ref(false);

watch(searchTerm, async (newTerm) => {
    if (newTerm.length < 2) {
        searchResults.value = [];
        showResultsDropdown.value = false;
        return;
    }
    isSearching.value = true;
    try {
        const response = await axios.get(route('busqueda.global'), {
            params: { term: newTerm }
        });
        searchResults.value = response.data;
        showResultsDropdown.value = searchResults.value.length > 0;
    } catch (error) {
        console.error('Error durante la búsqueda global:', error);
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
});

const goToResult = (url) => {
  router.get(url, {}, {
    preserveState: false, // Carga la página completa para aplicar los filtros
  });
  searchTerm.value = '';
  searchResults.value = [];
  showResultsDropdown.value = false;
};

// Cierra el dropdown si se hace clic fuera
const closeDropdown = () => {
    setTimeout(() => {
        showResultsDropdown.value = false;
    }, 200); // Pequeño delay para permitir el clic en el resultado
};

</script>

<template>
    <header class="z-30 py-3 shadow-lg vet-header">
        <div
            class="container flex items-center justify-between h-full px-6 mx-auto text-emerald-50"
        >
            <button
                class="p-1 mr-5 -ml-1 rounded-md lg:hidden text-emerald-100 hover:bg-emerald-700/50 focus:outline-none"
                @click="toggleSideMenu"
                aria-label="Menu"
            >
                <svg
                    class="w-6 h-6"
                    aria-hidden="true"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path
                        fill-rule="evenodd"
                        d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                        clip-rule="evenodd"
                    ></path>
                </svg>
            </button>

            <div class="flex justify-center flex-1 lg:mr-32">
                <div class="relative w-full max-w-xl mr-6 focus-within:text-emerald-200">
                    <div class="absolute inset-y-0 flex items-center pl-2">
                        <svg
                            class="w-4 h-4"
                            aria-hidden="true"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                clip-rule="evenodd"
                            ></path>
                        </svg>
                    </div>
                    <input
                        v-model="searchTerm"
                        @focus="showResultsDropdown = true"
                        @blur="closeDropdown"
                        class="w-full pl-8 pr-2 text-sm rounded-lg border border-emerald-600/40 bg-emerald-900/30 text-emerald-50 placeholder-emerald-200/60 focus:outline-none focus:ring-2 focus:ring-emerald-400/50 focus:bg-emerald-900/50"
                        type="text"
                        placeholder="Búsqueda global..."
                        aria-label="Search"
                    />
                    <div v-if="showResultsDropdown && searchTerm.length > 1" class="absolute left-0 right-0 mt-2 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg overflow-hidden">
                        <ul>
                            <li v-if="isSearching" class="px-4 py-2 text-sm text-gray-500">Buscando...</li>
                            <li v-else-if="searchResults.length === 0" class="px-4 py-2 text-sm text-gray-500">No se encontraron resultados.</li>
                            <li
                                v-for="(result, index) in searchResults"
                                :key="index"
                                @click="goToResult(result.url)"
                                class="cursor-pointer hover:bg-emerald-50 dark:hover:bg-gray-700 p-3 border-b border-gray-200 dark:border-gray-700 last:border-b-0"
                            >
                                <p class="font-semibold text-sm text-gray-800 dark:text-gray-200">{{ result.title }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ result.description }}</p>
                                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ result.type }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <ul class="flex items-center flex-shrink-0 space-x-4">
                <li class="flex items-center gap-2" title="Accesibilidad y tema">
                    <AccessibilityPanel variant="header" />
                    <ThemeSwitcher />
                </li>

                <li class="relative flex items-center space-x-3" ref="userMenuRef">
                    <span class="hidden md:block text-sm text-emerald-100">{{ user.first_name }} {{ user.last_name }}</span>
                    <button
                        type="button"
                        class="rounded-full ring-2 ring-transparent hover:ring-emerald-300/60 focus:outline-none focus:ring-emerald-300"
                        aria-label="Menú de usuario"
                        :aria-expanded="userMenuOpen"
                        @click="userMenuOpen = !userMenuOpen"
                    >
                        <FwbAvatar
                            :img="user.profile_photo_url"
                            rounded
                            class="cursor-pointer"
                        />
                    </button>

                    <transition
                        enter-active-class="transition ease-out duration-100"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div v-if="userMenuOpen" class="user-menu" role="menu">
                            <div class="user-menu__header">
                                <p class="font-semibold text-emerald-900">{{ user.first_name }} {{ user.last_name }}</p>
                                <p class="text-xs text-emerald-700/70 truncate">{{ user.email }}</p>
                            </div>
                            <Link
                                :href="route('profile.show')"
                                class="user-menu__item"
                                role="menuitem"
                                @click="closeUserMenu"
                            >
                                <i class="fa-solid fa-user w-5 text-emerald-600"></i>
                                Mi perfil
                            </Link>
                            <Link
                                :href="route('profile.show')"
                                class="user-menu__item"
                                role="menuitem"
                                @click="closeUserMenu"
                            >
                                <i class="fa-solid fa-shield-halved w-5 text-emerald-600"></i>
                                Seguridad y contraseña
                            </Link>
                            <hr class="border-emerald-100 my-1" />
                            <button
                                type="button"
                                class="user-menu__item user-menu__item--danger w-full text-left"
                                role="menuitem"
                                @click="logout"
                            >
                                <i class="fa-solid fa-right-from-bracket w-5"></i>
                                Cerrar sesión
                            </button>
                        </div>
                    </transition>
                </li>
            </ul>
        </div>
    </header>
</template>

<style scoped>
.vet-header {
    background: linear-gradient(90deg, var(--color-header-from) 0%, var(--color-header-mid) 50%, var(--color-header-to) 100%);
    border-bottom: 1px solid color-mix(in srgb, var(--color-border) 40%, transparent);
}

.user-menu {
    position: absolute;
    right: 0;
    top: calc(100% + 0.5rem);
    z-index: 50;
    width: 15rem;
    padding: 0.35rem;
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 0.75rem;
    box-shadow: 0 10px 25px color-mix(in srgb, var(--color-primary) 18%, transparent);
}

.user-menu__header {
    padding: 0.625rem 0.75rem;
    border-bottom: 1px solid var(--color-border);
    margin-bottom: 0.25rem;
}

.user-menu__item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    color: var(--color-text-base);
    transition: background 0.12s;
}

.user-menu__item:hover {
    background: var(--color-surface-hover);
}

.user-menu__item--danger {
    color: #b91c1c;
}

.user-menu__item--danger:hover {
    background: #fef2f2;
}
</style>
