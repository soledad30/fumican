<script setup>
import { computed } from "vue";

// Props para configurar el componente genérico
const props = defineProps({
    modelValue: [String, Number, null], // El valor seleccionado (v-model)
    options: Array,                    // Lista de opciones (array de objetos)
    label: String,                     // Etiqueta para el select
    optionValue: {                     // Clave del valor único de las opciones
        type: String,
        default: "id",                 // Por defecto, usa "id"
    },
    optionLabel: {                     // Clave del texto visible en las opciones
        type: String,
        default: "name",               // Por defecto, usa "name"
    },
    placeholder: {                     // Placeholder opcional
        type: String,
        default: "Seleccionar una opción",
    },
    disabled: {                        // Habilitar o deshabilitar el select
        type: Boolean,
        default: false,
    },
});

// Emitir evento para actualizar el v-model
const emit = defineEmits(["update:modelValue"]);
const handleChange = (event) => {
    emit("update:modelValue", event.target.value);
};

// Computed para mostrar el placeholder como opción seleccionada
const selectedValue = computed(() => props.modelValue || "");
</script>

<template>
    <div>
        <!-- Etiqueta -->
        <label
            v-if="label"
            :for="label"
            class="block text-sm font-medium text-gray-700"
        >
            {{ label }}
        </label>

        <!-- Select -->
        <select
            :id="label"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
            :value="selectedValue"
            :disabled="disabled"
            @change="handleChange"
        >
            <!-- Placeholder -->
            <option value="" disabled>
                {{ placeholder }}
            </option>

            <!-- Opciones dinámicas -->
            <option
                v-for="option in options"
                :key="option[optionValue]"
                :value="option[optionValue]"
            >
                {{ option[optionLabel] }}
            </option>
        </select>
    </div>
</template>

<style scoped>
/* Personaliza los estilos aquí si es necesario */
</style>
