<script setup>
defineProps({
    canView: { type: Boolean, default: false },
    canEdit: { type: Boolean, default: true },
    canDelete: { type: Boolean, default: true },
    viewTitle: { type: String, default: "Ver detalles" },
    editTitle: { type: String, default: "Editar" },
    deleteTitle: { type: String, default: "Eliminar" },
});

defineEmits(["view", "edit", "delete"]);
</script>

<template>
    <div class="flex items-center gap-2">
        <button
            v-if="canView"
            type="button"
            class="vet-action-btn vet-action-btn--view"
            :title="viewTitle"
            @click="$emit('view')"
        >
            <i class="fa-solid fa-eye fa-lg"></i>
        </button>
        <button
            v-if="canEdit"
            type="button"
            class="vet-action-btn vet-action-btn--edit"
            :title="editTitle"
            @click="$emit('edit')"
        >
            <i class="fa-solid fa-pencil fa-lg"></i>
        </button>
        <button
            v-if="canDelete"
            type="button"
            class="vet-action-btn vet-action-btn--delete"
            :title="deleteTitle"
            @click="$emit('delete')"
        >
            <i class="fa-solid fa-trash fa-lg"></i>
        </button>
    </div>
</template>
