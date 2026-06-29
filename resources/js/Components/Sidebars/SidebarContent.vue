<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    openSubMenu: String,
    toggleSubMenu: Function,
    onNavigate: { type: Function, default: null },
});

const page = usePage();
const menus = computed(() => page.props.auth.user_menus ?? []);
const rolActivo = computed(() => page.props.auth.user?.role?.name ?? page.props.auth.user?.role?.nombre ?? "Usuario");

function linkPath(link) {
    if (!link) return "";
    if (link.startsWith("http")) {
        try {
            return new URL(link).pathname;
        } catch {
            return link;
        }
    }
    return link.split("?")[0];
}

function isActive(link) {
    if (!link) return false;
    const url = page.url.split("?")[0];
    const path = linkPath(link);
    if (path === "/dashboard") return url === "/dashboard";
    return url === path || url.startsWith(path + "/");
}

function menuActivo(menu) {
    if (menu.link && isActive(menu.link)) return true;
    return menu.submenus?.some((s) => isActive(s.link));
}

function alNavegar() {
    props.onNavigate?.();
}
</script>

<template>
    <div class="vet-sidebar-inner flex flex-col h-full">
        <div class="px-5 pt-6 pb-4 border-b border-emerald-700/60">
            <div class="flex items-center gap-3 mb-1">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center">
                    <i class="fa-solid fa-paw text-emerald-200 text-lg"></i>
                </div>
                <div>
                    <p class="text-white font-bold text-base leading-tight">Fumican Vet</p>
                    <p class="text-emerald-300/80 text-xs">Clínica veterinaria</p>
                </div>
            </div>
        </div>

        <div class="px-4 py-4">
            <label class="block text-[10px] font-semibold tracking-widest text-emerald-300/70 uppercase mb-2">
                Rol activo
            </label>
            <div class="flex items-center gap-2 w-full px-3 py-2.5 rounded-lg border border-emerald-600/70 bg-emerald-900/50 text-emerald-50 text-sm">
                <i class="fa-solid fa-user-doctor text-emerald-300 text-xs"></i>
                <span class="flex-1 truncate font-medium">{{ rolActivo }}</span>
                <i class="fa-solid fa-chevron-down text-emerald-400/70 text-xs"></i>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 pb-6">
            <ul class="space-y-1">
                <template v-for="menu in menus" :key="menu.name">
                    <li v-if="menu.submenus?.length > 0 || menu.link">
                        <Link
                            v-if="!menu.submenus?.length"
                            :href="menu.link"
                            @click="alNavegar"
                            class="vet-nav-link"
                            :class="{ 'vet-nav-link--active': isActive(menu.link) }"
                        >
                            <i :class="[menu.icon, 'w-5 text-center text-sm']" />
                            <span class="flex-1">{{ menu.name }}</span>
                        </Link>

                        <div v-else class="space-y-0.5">
                            <div class="flex items-center gap-1">
                                <Link
                                    v-if="menu.link"
                                    :href="menu.link"
                                    @click="alNavegar"
                                    class="vet-nav-link flex-1 min-w-0"
                                    :class="{ 'vet-nav-link--active': isActive(menu.link) }"
                                >
                                    <i :class="[menu.icon, 'w-5 text-center text-sm shrink-0']" />
                                    <span class="truncate">{{ menu.name }}</span>
                                </Link>
                                <button
                                    v-else
                                    type="button"
                                    class="vet-nav-link flex-1"
                                    :class="{ 'vet-nav-link--active': menuActivo(menu) }"
                                    @click="toggleSubMenu(menu.name)"
                                >
                                    <i :class="[menu.icon, 'w-5 text-center text-sm']" />
                                    <span class="flex-1 text-left">{{ menu.name }}</span>
                                </button>
                                <button
                                    v-if="menu.submenus?.length"
                                    type="button"
                                    class="p-2 rounded-lg text-emerald-300/80 hover:bg-emerald-700/40 hover:text-white transition-colors"
                                    @click="toggleSubMenu(menu.name)"
                                    :aria-label="`Submenú ${menu.name}`"
                                >
                                    <i
                                        class="fa-solid fa-chevron-down text-xs transition-transform duration-200"
                                        :class="{ 'rotate-180': openSubMenu === menu.name }"
                                    />
                                </button>
                            </div>
                            <ul
                                v-show="openSubMenu === menu.name"
                                class="ml-4 pl-3 border-l border-emerald-600/40 space-y-0.5 vet-sidebar-submenu"
                            >
                                <li v-for="sub in menu.submenus" :key="sub.name">
                                    <Link
                                        :href="sub.link"
                                        @click="alNavegar"
                                        class="vet-nav-sublink"
                                        :class="{ 'vet-nav-sublink--active': isActive(sub.link) }"
                                    >
                                        {{ sub.name }}
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </li>
                </template>
            </ul>
        </nav>
    </div>
</template>

<style scoped>
.vet-nav-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.625rem 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-sidebar-text-muted);
    transition: all 0.15s;
}
.vet-nav-link:hover {
    background: color-mix(in srgb, var(--color-primary-hover) 45%, transparent);
    color: var(--color-sidebar-text);
}
.vet-nav-link--active {
    background: color-mix(in srgb, var(--color-primary-hover) 55%, transparent);
    color: var(--color-sidebar-text);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
}
.vet-nav-sublink {
    display: block;
    padding: 0.5rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    color: var(--color-sidebar-text-muted);
    transition: color 0.15s, background 0.15s;
}
.vet-nav-sublink:hover {
    background: color-mix(in srgb, var(--color-primary-hover) 35%, transparent);
    color: var(--color-sidebar-text);
}
.vet-nav-sublink--active {
    background: color-mix(in srgb, var(--color-primary-hover) 40%, transparent);
    color: var(--color-sidebar-text);
    font-weight: 500;
}

.vet-sidebar-submenu {
    border-color: color-mix(in srgb, var(--color-sidebar-text) 25%, transparent) !important;
}
</style>
