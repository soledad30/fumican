<script setup>
import { FwbInput, FwbSpinner } from "flowbite-vue";
import { computed } from "vue";

const props = defineProps({
    search: String,
    isFetchingData: Boolean,
    results: Array,
    title: String,
    placeholder: String,
    nested: { type: Boolean, default: false },
    layout: { type: String, default: "list" },
    hint: { type: String, default: "" },
});

const emit = defineEmits(["update:search", "select", "close"]);

const hasResults = computed(() => (props.results?.length ?? 0) > 0);
const showNoResults = computed(
    () =>
        !props.isFetchingData &&
        props.search?.trim() &&
        !hasResults.value
);
</script>

<template>
    <Teleport to="body">
        <div
            class="search-modal-backdrop"
            :class="{ 'search-modal-backdrop--nested': nested }"
            @click.self="$emit('close')"
        >
            <div
                class="search-modal-panel"
                :class="{ 'search-modal-panel--nested': nested }"
                role="dialog"
                aria-modal="true"
            >
                <header class="search-modal-header">
                    <div>
                        <h3 class="search-modal-title">{{ title }}</h3>
                        <p v-if="hint" class="search-modal-hint">{{ hint }}</p>
                    </div>
                    <button
                        type="button"
                        class="search-modal-close"
                        aria-label="Cerrar"
                        @click="$emit('close')"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </header>

                <div class="search-modal-body">
                    <FwbInput
                        :modelValue="search"
                        @update:modelValue="$emit('update:search', $event)"
                        :placeholder="placeholder"
                        label="Buscar"
                        size="lg"
                        class="search-modal-input"
                    >
                        <template #prefix>
                            <i
                                class="fa-solid fa-magnifying-glass search-modal-search-icon ml-1"
                            ></i>
                        </template>
                        <template v-if="isFetchingData" #suffix>
                            <FwbSpinner size="6" />
                        </template>
                    </FwbInput>

                    <div v-if="layout === 'table'" class="search-modal-table-wrap">
                        <table class="search-modal-table">
                            <thead>
                                <slot name="thead" />
                            </thead>
                            <tbody>
                                <tr
                                    v-for="result in results"
                                    :key="result.id"
                                    class="search-modal-row"
                                    @click="$emit('select', result)"
                                >
                                    <slot name="result" :result="result" />
                                </tr>
                            </tbody>
                        </table>

                        <div
                            v-if="isFetchingData"
                            class="search-modal-state"
                        >
                            <FwbSpinner size="8" />
                            <p>Cargando propietarios...</p>
                        </div>

                        <div
                            v-else-if="showNoResults"
                            class="search-modal-state"
                        >
                            <i class="fa-solid fa-user-slash text-2xl opacity-50"></i>
                            <p>No se encontraron resultados.</p>
                            <span class="text-xs opacity-70">
                                Pruebe con otro nombre o CI.
                            </span>
                        </div>

                        <div
                            v-else-if="!hasResults"
                            class="search-modal-state"
                        >
                            <i class="fa-solid fa-users text-2xl opacity-50"></i>
                            <p>No hay propietarios registrados.</p>
                        </div>
                    </div>

                    <div v-else class="search-modal-list-wrap">
                        <div
                            v-if="isFetchingData"
                            class="search-modal-state"
                        >
                            <FwbSpinner size="8" />
                            <p>Buscando...</p>
                        </div>

                        <div
                            v-else-if="showNoResults"
                            class="search-modal-state"
                        >
                            <p>No se encontraron resultados.</p>
                        </div>

                        <ul v-else-if="hasResults" class="search-modal-list">
                            <li
                                v-for="result in results"
                                :key="result.id"
                                class="search-modal-list-item"
                                @click="$emit('select', result)"
                            >
                                <slot name="prefix" />
                                <slot name="result" :result="result" />
                            </li>
                        </ul>
                    </div>
                </div>

                <footer class="search-modal-footer">
                    <button
                        type="button"
                        class="search-modal-btn search-modal-btn--ghost"
                        @click="$emit('close')"
                    >
                        Cerrar
                    </button>
                </footer>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.search-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: color-mix(in srgb, var(--color-text-base) 35%, transparent);
    backdrop-filter: blur(2px);
}

.search-modal-backdrop--nested {
    z-index: 60;
    background: color-mix(in srgb, var(--color-text-base) 50%, transparent);
    backdrop-filter: blur(4px);
}

.search-modal-panel {
    width: 100%;
    max-width: 32rem;
    max-height: min(90vh, 640px);
    display: flex;
    flex-direction: column;
    border-radius: 1rem;
    overflow: hidden;
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    box-shadow: 0 25px 50px -12px color-mix(in srgb, var(--color-text-base) 20%, transparent);
    color: var(--color-text-base);
}

.search-modal-panel--nested {
    max-width: 42rem;
    border: 2px solid var(--color-primary);
    box-shadow:
        0 0 0 4px color-mix(in srgb, var(--color-primary) 18%, transparent),
        0 25px 50px -12px color-mix(in srgb, var(--color-text-base) 28%, transparent);
}

.search-modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.1rem 1.25rem;
    background: linear-gradient(
        135deg,
        var(--color-header-from) 0%,
        var(--color-header-mid) 50%,
        var(--color-header-to) 100%
    );
    color: var(--color-header-text);
}

.search-modal-title {
    font-size: 1.125rem;
    font-weight: 600;
    line-height: 1.3;
}

.search-modal-hint {
    margin-top: 0.25rem;
    font-size: 0.8rem;
    opacity: 0.92;
    color: var(--color-header-text);
}

.search-modal-close {
    flex-shrink: 0;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
    color: var(--color-header-text);
    background: color-mix(in srgb, var(--color-header-text) 18%, transparent);
    transition: background 0.15s;
}

.search-modal-close:hover {
    background: color-mix(in srgb, var(--color-header-text) 30%, transparent);
}

.search-modal-body {
    padding: 1rem 1.25rem;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    background: linear-gradient(
        180deg,
        var(--color-primary-light) 0%,
        var(--color-background) 100%
    );
    flex: 1;
    min-height: 0;
}

.search-modal-search-icon {
    color: var(--color-primary);
}

.search-modal-table-wrap {
    flex: 1;
    min-height: 0;
    overflow: auto;
    border: 1px solid var(--color-border);
    border-radius: 0.75rem;
    background: var(--color-surface);
}

.search-modal-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.search-modal-table :deep(th) {
    position: sticky;
    top: 0;
    z-index: 1;
    padding: 0.65rem 0.75rem;
    text-align: left;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--color-on-table-head);
    background: var(--vet-table-head);
    border-bottom: 1px solid var(--color-border);
}

.search-modal-row {
    cursor: pointer;
    transition: background 0.12s;
}

.search-modal-row:hover {
    background: var(--color-surface-hover);
}

.search-modal-row:active {
    background: var(--color-primary-soft);
}

.search-modal-table :deep(td) {
    padding: 0.7rem 0.75rem;
    border-bottom: 1px solid var(--color-border);
    color: var(--color-text-base);
    vertical-align: middle;
}

.search-modal-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 2.5rem 1rem;
    text-align: center;
    color: var(--color-text-muted);
    font-size: 0.9rem;
}

.search-modal-list-wrap {
    max-height: 16rem;
    overflow: auto;
}

.search-modal-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.search-modal-list-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    border-radius: 0.5rem;
    cursor: pointer;
    border: 1px solid transparent;
    color: var(--color-text-base);
}

.search-modal-list-item:hover {
    background: var(--color-surface-hover);
    border-color: var(--color-border);
}

.search-modal-footer {
    display: flex;
    justify-content: flex-end;
    padding: 0.85rem 1.25rem;
    border-top: 1px solid var(--color-border);
    background: var(--color-surface);
}

.search-modal-btn {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.search-modal-btn--ghost {
    color: var(--color-text-muted);
    border: 1px solid var(--color-border);
    background: var(--color-surface);
}

.search-modal-btn--ghost:hover {
    background: var(--color-surface-hover);
    color: var(--color-text-base);
}

:deep(.search-modal-input label) {
    color: var(--color-text-base);
    font-weight: 500;
}
</style>

<style>
.search-modal-select-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.6rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--color-primary-hover);
    background: var(--color-primary-soft);
    transition: background 0.12s, color 0.12s;
}

.search-modal-row:hover .search-modal-select-btn {
    background: var(--color-primary);
    color: var(--color-on-primary);
}
</style>
