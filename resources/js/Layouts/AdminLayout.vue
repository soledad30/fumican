<script setup>
import { ref, onMounted } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import Banner from "@/Components/Banner.vue";
import Sidebar from "@/Components/Sidebars/Sidebar.vue";
import MobileSidebar from "@/Components/Sidebars/MobileSidebar.vue";
import Header from "@/Components/Headers/Header.vue";
import Footer from "@/Components/Footers/AdminFooter.vue";
import { useTheme } from '@/Composables/useTheme';

// Inicializa el sistema de temas
const { initTheme } = useTheme();
onMounted(() => {
    initTheme();
    const page = usePage();
    const url = page.url.split("?")[0];
    for (const menu of page.props.auth.user_menus ?? []) {
        if (menu.submenus?.some((s) => url === s.link || url.startsWith(s.link + "/"))) {
            openSubMenu.value = menu.name;
            break;
        }
    }
});

// --- Component Props ---
defineProps({
    title: String
});

// --- Layout State Management ---
const showingNavigationDropdown = ref(false);
const openSubMenu = ref(null);

const toggleSideMenu = () => {
    showingNavigationDropdown.value = !showingNavigationDropdown.value;
};

const toggleSubMenu = (menuName) => {
    openSubMenu.value = openSubMenu.value === menuName ? null : menuName;
};

const closeSideMenu = () => {
    showingNavigationDropdown.value = false;
};
</script>

<template>
    <div>
        <Head :title="title" />
        <Banner />
        <div class="flex h-screen page-background" :class="{ 'overflow-hidden': showingNavigationDropdown }">
            <!-- Sidebar para Desktop -->
            <Sidebar
                :openSubMenu="openSubMenu"
                :toggleSubMenu="toggleSubMenu"
            />

            <!-- Sidebar para Mobile -->
            <MobileSidebar
                :showing-navigation-dropdown="showingNavigationDropdown"
                @close-side-menu="closeSideMenu"
            />

            <div class="flex flex-col flex-1 w-full">
                <Header
                    :user="$page.props.auth.user"
                    :toggleSideMenu="toggleSideMenu"
                />
                <main class="flex-1 min-h-0 overflow-y-auto vet-main-area vet-admin">
                    <div class="container px-4 sm:px-6 py-4 sm:py-6 mx-auto w-full min-w-0">
                        <slot />
                    </div>
                </main>
                <Footer :visit-count="$page.props.visitCount ?? 0" />
            </div>
        </div>
    </div>
</template>

<style>
.page-background {
    background-color: var(--color-background);
    color: var(--color-text-base);
}

.th {
    background-color: var(--vet-table-head);
    color: var(--color-text-base);
}
</style>
