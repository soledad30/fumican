<script setup>
import { ref } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import axios from "axios";

const showToast = ref(false);
const toastMsg = ref("");
const toastType = ref("success");

const prompt = ref("");
const result = ref({});
const loading = ref(false);

const clearPrompt = () => {
    prompt.value = "";
    result.value = {};
};

const submitPrompt = async () => {
    loading.value = true;
    result.value = "";
    try {
        const response = await axios.post(
            route("calidad.prompt.generate"),
            {
                prompt: prompt.value,
            },
            {
                headers: {
                    "Content-Type": "application/json",
                },
            }
        );
        result.value = response.data || "No se obtuvo respuesta.";

        toastMsg.value = "Diagnóstico generado correctamente.";
        toastType.value = "success";
        showToast.value = true;
    } catch (error) {
        result.value = "Error al obtener el diagnóstico.";

        toastMsg.value = "Hubo un error al conectar con Gemini.";
        toastType.value = "danger";
        showToast.value = true;
    } finally {
        loading.value = false;

        // Opcional: ocultar toast después de 3 segundos
        setTimeout(() => {
            showToast.value = false;
        }, 3000);
    }
};
</script>

<template>
    <AdminLayout title="Proveedores">
        <!-- Toast Notification -->
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType">{{
                toastMsg
            }}</FwbToast>
        </div>
        <div class="w-3/4 mx-auto mt-10 p-6 bg-white rounded shadow">
            <h2 class="text-2xl font-bold mb-4">
                Diagnóstico Veterinario
            </h2>
            <form @submit.prevent="submitPrompt">
                <label class="block mb-2 font-semibold"
                    >Describe los síntomas o enfermedad:</label
                >
                <textarea
                    v-model="prompt"
                    class="w-full border rounded p-2 mb-4"
                    rows="4"
                    placeholder="Ej. Mi perro tiene fiebre y no quiere comer..."
                    required
                ></textarea>
                <button
                    type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
                    :disabled="loading"
                    @click="submitPrompt"
                >
                    {{ loading ? "Consultando..." : "Consultar" }}
                </button>
                <button
                    type="button"
                    class="ml-2 bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400"
                    @click="clearPrompt"
                >
                    Limpiar
                </button>
            </form>
            <div
                v-if="result.diagnostico"
                class="mt-6 p-4 bg-white rounded shadow"
            >
                <h3 class="text-xl font-bold mb-2">Diagnóstico:</h3>
                <p class="mb-4">{{ result.diagnostico }}</p>

                <h4 class="font-semibold">Causas posibles:</h4>
                <ul class="list-disc pl-5 mb-4">
                    <li v-for="(causa, i) in result.causas" :key="'causa-' + i">
                        {{ causa }}
                    </li>
                </ul>

                <h4 class="font-semibold">Recomendaciones:</h4>
                <ul class="list-disc pl-5 mb-4">
                    <li
                        v-for="(reco, i) in result.recomendaciones"
                        :key="'reco-' + i"
                    >
                        {{ reco }}
                    </li>
                </ul>

                <h4
                    v-if="result.medicamentos && result.medicamentos.length"
                    class="font-semibold"
                >
                    Medicamentos sugeridos:
                </h4>
                <ul
                    v-if="result.medicamentos && result.medicamentos.length"
                    class="list-disc pl-5"
                >
                    <li
                        v-for="(med, i) in result.medicamentos"
                        :key="'med-' + i"
                    >
                        {{ med }}
                    </li>
                </ul>
            </div>
            <div v-else-if="result" class="mt-6 p-4 bg-white rounded shadow">
                <h3 class="text-xl font-bold mb-2">Resultado:</h3>
                <p>{{ result }}</p>
            </div>
        </div>
    </AdminLayout>
</template>
