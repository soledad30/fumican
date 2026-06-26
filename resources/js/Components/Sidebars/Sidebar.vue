<script setup>
import SidebarContent from "@/Components/Sidebars/SidebarContent.vue";

defineProps({
    openSubMenu: String,
    toggleSubMenu: Function,
});
</script>

<template>
    <aside class="vet-sidebar z-20 hidden w-64 overflow-y-auto lg:block flex-shrink-0 shadow-xl">
        <SidebarContent :open-sub-menu="openSubMenu" :toggle-sub-menu="toggleSubMenu" />
    </aside>
</template>

<style scoped>
.vet-sidebar {
    background: linear-gradient(180deg, var(--color-sidebar-from) 0%, var(--color-sidebar-mid) 45%, var(--color-sidebar-to) 100%);
}
</style>
