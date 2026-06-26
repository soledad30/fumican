<script setup>
import { ref, computed } from "vue";
import axios from "axios";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { router } from "@inertiajs/vue3";
import { FwbToast } from "flowbite-vue";

// Props del backend
const props = defineProps({
    customers: Array,
    warehouses: Array,
    medicamentsList: Array,
    salesNote: Object,
    salesNoteDetails: Array,
});

// Formulario reactivo
const form = ref({
    customer_id: props.salesNote.customer_id,
    warehouse_id: props.salesNote.warehouse_id,
    medicaments: props.salesNoteDetails.map((detail) => ({
        detail_id: detail.id,
        id: detail.medicament_id,
        quantity: Number(detail.quantity),
        sale_price: Number(detail.sale_price),
        subtotal: Number(detail.subtotal),
    })),
    total_amount: Number(props.salesNote.total_amount),
    processing: false,
});

// Toasts
const showToast = ref(false);
const toastMsg = ref("");
const toastType = ref("success");

// Computed title
const actionTitle = computed(() => "Editar");

// Funciones para manipular medicamentos y totales
function addMedicament() {
    form.value.medicaments.push({
        id: "",
        quantity: 1,
        sale_price: 0,
        subtotal: 0,
    });
    updateTotalAmount();
}
function removeMedicament(index) {
    form.value.medicaments.splice(index, 1);
    updateTotalAmount();
}
function updateTotalAmount() {
    let total = 0;
    form.value.medicaments.forEach((m) => {
        m.subtotal = Number(m.quantity) * Number(m.sale_price);
        total += m.subtotal;
    });
    form.value.total_amount = total;
}

function aplicarPrecioSugerido(med) {
    const product = props.medicamentsList.find(
        (m) => String(m.id) === String(med.id),
    );
    const precio = product?.reference_sale_price;
    med.sale_price =
        precio != null && precio !== "" ? Number(precio) : 0;
    updateTotalAmount();
}

// Submit con Axios para recibir respuesta JSON del backend
async function submit() {
    form.value.processing = true;
    try {
        const { data } = await axios.put(
            route("notas-venta.update", props.salesNote.id),
            form.value
        );
        toastType.value = "success";
        toastMsg.value =
            data.message || "Nota de venta actualizada exitosamente";
        showToast.value = true;
        setTimeout(() => router.get(route("notas-venta.index")), 1000);
    } catch (e) {
        toastType.value = "danger";
        toastMsg.value =
            e.response?.data?.message || "Error al actualizar la nota de venta";
        showToast.value = true;
    } finally {
        form.value.processing = false;
    }
}

function cancel() {
    router.get(route("notas-venta.index"));
}
</script>

<template>
    <AdminLayout :title="actionTitle + ' Nota de Venta'">
        <!-- Toast notificación -->
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType">{{
                toastMsg
            }}</FwbToast>
        </div>
        <div class="container mx-auto p-6">
            <h2 class="text-2xl font-semibold vet-page-title mb-4">
                {{ actionTitle }} Nota de Venta
            </h2>
            <form @submit.prevent="submit">
                <!-- Cliente -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-600"
                        >Cliente</label
                    >
                    <select
                        v-model="form.customer_id"
                        class="mt-1 block w-full p-3 border rounded-md focus:ring-2 focus:ring-emerald-500"
                        required
                    >
                        <option value="">Seleccionar</option>
                        <option
                            v-for="c in customers"
                            :key="c.id"
                            :value="c.id"
                        >
                            {{ c.first_name }} {{ c.last_name }}
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
                        class="mt-1 block w-full p-3 border rounded-md focus:ring-2 focus:ring-emerald-500"
                        required
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
                    <h3 class="text-xl font-semibold mb-4">Medicamentos</h3>
                    <div
                        v-for="(med, idx) in form.medicaments"
                        :key="idx"
                        class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4"
                    >
                        <select
                            v-model="med.id"
                            @change="aplicarPrecioSugerido(med)"
                            class="mt-1 block w-full p-3 border rounded-md focus:ring-2 focus:ring-emerald-500"
                            required
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
                        <input
                            v-model.number="med.quantity"
                            type="number"
                            min="1"
                            @input="updateTotalAmount"
                            class="mt-1 block w-full p-3 border rounded-md focus:ring-2 focus:ring-emerald-500"
                            required
                        />
                        <input
                            v-model.number="med.sale_price"
                            type="number"
                            min="0"
                            step="0.01"
                            title="Precio sugerido según última nota de compra (editable)"
                            @input="updateTotalAmount"
                            class="mt-1 block w-full p-3 border rounded-md focus:ring-2 focus:ring-emerald-500 bg-emerald-50"
                            required
                        />
                        <div class="flex items-center justify-between mt-4">
                            <span class="font-medium"
                                >{{ med.subtotal.toFixed(2) }} Bs</span
                            >
                            <button
                                type="button"
                                @click="removeMedicament(idx)"
                                class="text-red-600 hover:text-red-800"
                            >
                                Eliminar
                            </button>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="addMedicament"
                        class="text-green-600 hover:text-green-800"
                    >
                        + Agregar Medicamento
                    </button>
                </div>
                <!-- Total General -->
                <div class="my-6">
                    <label class="block text-sm font-medium text-gray-600"
                        >Total General</label
                    >
                    <input
                        :value="form.total_amount.toFixed(2)"
                        readonly
                        class="mt-1 block w-full p-3 border rounded-md focus:ring-2 focus:ring-emerald-500 bg-gray-100 text-right"
                    />
                </div>
                <!-- Botones -->
                <div class="flex justify-end space-x-4">
                    <button
                        type="button"
                        @click="cancel"
                        class="bg-gray-300 text-black px-6 py-2 rounded-md hover:bg-gray-700 hover:text-white"
                        :disabled="form.processing"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-emerald-700 text-white px-6 py-2 rounded-md hover:bg-emerald-800"
                    >
                        {{ actionTitle }} Nota de Venta
                    </button>
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
