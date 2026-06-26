<script setup>
import { ref } from "vue";
import SidebarContent from "@/Components/Sidebars/SidebarContent.vue";

defineProps({
    showingNavigationDropdown: Boolean,
});

defineEmits(["close-side-menu"]);

const openSubMenu = ref(null);

const toggleSubMenu = (menuName) => {
    openSubMenu.value = openSubMenu.value === menuName ? null : menuName;
};
</script>

<template>
    <transition
        enter-active-class="transition ease-in-out duration-150"
        enter-from-class="opacity-0 transform -translate-x-20"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in-out duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0 transform -translate-x-20"
    >
        <aside
            v-if="showingNavigationDropdown"
            class="vet-sidebar fixed inset-y-0 z-20 flex-shrink-0 w-64 mt-16 overflow-y-auto lg:hidden shadow-xl"
        >
            <SidebarContent
                :open-sub-menu="openSubMenu"
                :toggle-sub-menu="toggleSubMenu"
                :on-navigate="() => $emit('close-side-menu')"
            />
        </aside>
    </transition>
</template>

<style scoped>
.vet-sidebar {
    background: linear-gradient(180deg, var(--color-sidebar-from) 0%, var(--color-sidebar-mid) 45%, var(--color-sidebar-to) 100%);
}
</style>
