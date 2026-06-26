<script setup>
import { ref, computed } from "vue";
import axios from "axios";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { router } from "@inertiajs/vue3";
import { FwbToast, FwbButton } from "flowbite-vue";

// Props
const props = defineProps({
    suppliers: Array,
    warehouses: Array,
    medicamentsList: Array,
    purchaseNote: Object,
    purchaseNoteDetails: Array,
});

// Inicializar total desde props
const initialTotal = parseFloat(props.purchaseNote.total_amount) || 0;

// Estado del formulario pre-llenado
const form = ref({
    id: props.purchaseNote.id,
    supplier_id: props.purchaseNote.supplier_id,
    warehouse_id: props.purchaseNote.warehouse_id,
    medicaments: props.purchaseNoteDetails.map((d) => ({
        detail_id: d.id,
        id: d.medicament_id,
        quantity: Number(d.quantity),
        purchase_price: Number(d.purchase_price),
        subtotal: Number(d.subtotal),
    })),
    total_amount: initialTotal,
    processing: false,
});

// Toast
const showToast = ref(false);
const toastMsg = ref("");
const toastType = ref("success");

// Título dinámico
const actionTitle = computed(() => "Editar");

// Recalcular subtotales y total
function updateTotal() {
    let total = 0;
    form.value.medicaments.forEach((m) => {
        m.subtotal = Number(m.quantity) * Number(m.purchase_price);
        total += m.subtotal;
    });
    form.value.total_amount = total;
}

// Agregar/quitar fila
function addMed() {
    form.value.medicaments.push({
        id: "",
        quantity: 1,
        purchase_price: 0,
        subtotal: 0,
    });
    updateTotal();
}
function removeMed(i) {
    form.value.medicaments.splice(i, 1);
    updateTotal();
}

// Cancelar y volver al índice
function cancel() {
    router.get(route("notas-compra.index"));
}

// Enviar PUT
async function submit() {
    form.value.processing = true;
    try {
        const { data } = await axios.put(
            route("notas-compra.update", form.value.id),
            form.value
        );
        toastType.value = "success";
        toastMsg.value = data.message;
        showToast.value = true;
        setTimeout(() => router.get(route("notas-compra.index")), 800);
    } catch (e) {
        toastType.value = "danger";
        toastMsg.value =
            e.response?.data?.message || "Error al actualizar nota";
        showToast.value = true;
    } finally {
        form.value.processing = false;
    }
}
</script>

<template>
    <AdminLayout :title="actionTitle + ' Nota de Compra'">
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType">{{
                toastMsg
            }}</FwbToast>
        </div>

        <div class="container mx-auto p-6">
            <h2 class="text-2xl font-semibold vet-page-title mb-4">
                {{ actionTitle }} Nota de Compra
            </h2>

            <form @submit.prevent="submit">
                <!-- Proveedor -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-600"
                        >Proveedor</label
                    >
                    <select
                        v-model="form.supplier_id"
                        required
                        class="mt-1 block w-full p-3 border rounded-md focus:ring-2 focus:ring-emerald-500"
                    >
                        <option value="">Seleccionar</option>
                        <option
                            v-for="s in suppliers"
                            :key="s.id"
                            :value="s.id"
                        >
                            {{ s.name }}
                        </option>
                    </select>
                </div>

                <!-- Almacén -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-600"
                        >Almacén</label
                    >
                    <select
                        v-model="form.warehouse_id"
                        required
                        class="mt-1 block w-full p-3 border rounded-md focus:ring-2 focus:ring-emerald-500"
                    >
                        <option value="">Seleccionar</option>
                        <option
                            v-for="w in warehouses"
                            :key="w.id"
                            :value="w.id"
                        >
                            {{ w.name }}
                        </option>
                    </select>
                </div>

                <!-- Medicamentos -->
                <div class="my-6">
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">
                        Medicamentos
                    </h3>

                    <!-- Encabezado de columnas -->
                    <div
                        class="grid grid-cols-1 md:grid-cols-4 gap-4 font-medium text-gray-700 mb-1"
                    >
                        <div>Medicamento</div>
                        <div class="text-right">Cantidad</div>
                        <div class="text-right">Precio (Bs)</div>
                        <div class="text-right">Subtotal</div>
                    </div>

                    <!-- Filas -->
                    <div
                        v-for="(med, i) in form.medicaments"
                        :key="i"
                        class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 items-center"
                    >
                        <!-- Select medicamento -->
                        <select
                            v-model="med.id"
                            @change="updateTotal"
                            required
                            class="block w-full p-3 border rounded-md focus:ring-2 focus:ring-emerald-500"
                        >
                            <option value="">Seleccionar</option>
                            <option
                                v-for="m in medicamentsList"
                                :key="m.id"
                                :value="m.id"
                            >
                                {{ m.name }}
                            </option>
                        </select>

                        <!-- Input cantidad -->
                        <input
                            type="number"
                            v-model.number="med.quantity"
                            min="1"
                            placeholder="Cant."
                            @input="updateTotal"
                            required
                            class="block w-full p-3 border rounded-md focus:ring-2 focus:ring-emerald-500 text-right"
                        />

                        <!-- Input precio -->
                        <input
                            type="number"
                            v-model.number="med.purchase_price"
                            step="0.01"
                            min="0"
                            placeholder="Precio"
                            @input="updateTotal"
                            required
                            class="block w-full p-3 border rounded-md focus:ring-2 focus:ring-emerald-500 text-right"
                        />

                        <!-- Subtotal y borrar -->
                        <div
                            class="flex items-center justify-between space-x-2"
                        >
                            <span class="font-semibold">{{
                                med.subtotal.toFixed(2)
                            }}</span>
                            <button
                                type="button"
                                @click="removeMed(i)"
                                class="text-red-600 hover:text-red-800"
                                title="Eliminar fila"
                            >
                                &times;
                            </button>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="addMed"
                        class="text-green-600 hover:text-green-800 font-medium"
                    >
                        + Agregar Medicamento
                    </button>
                </div>

                <!-- Total general -->
                <div class="my-6">
                    <label class="block text-sm font-medium text-gray-600"
                        >Total General</label
                    >
                    <input
                        type="text"
                        :value="form.total_amount.toFixed(2)"
                        readonly
                        class="mt-1 block w-full p-3 border rounded-md bg-gray-100 text-right"
                    />
                </div>

                <!-- Botones -->
                <div class="flex justify-end space-x-4">
                    <FwbButton color="alternative" @click="cancel"
                        >Cancelar</FwbButton
                    >
                    <FwbButton type="submit" :disabled="form.processing">
                        {{ actionTitle }}
                    </FwbButton>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<style scoped>
.container {
    max-width: 900px;
    margin: 0 auto;
}
</style>
