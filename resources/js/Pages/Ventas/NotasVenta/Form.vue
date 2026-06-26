<script setup>
import { ref, computed } from "vue";
import axios from "axios";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { router } from "@inertiajs/vue3";
import { FwbToast, FwbButton } from "flowbite-vue";

// Props recibidas del backend
const props = defineProps({
    customers: Array,
    warehouses: Array,
    medicamentsList: Array,
});

// Estado del formulario
const form = ref({
    customer_id: "",
    warehouse_id: "",
    medicaments: [{ id: "", quantity: 1, sale_price: 0, subtotal: 0 }],
    total_amount: 0,
    processing: false,
});

// Toast de notificaciones
const showToast = ref(false);
const toastMsg = ref("");
const toastType = ref("success");

// Título dinámico
const actionTitle = computed(() => "Agregar");

// Actualizar total y subtotal
const updateTotal = () => {
    let total = 0;
    form.value.medicaments.forEach((m) => {
        m.subtotal = Number(m.quantity) * Number(m.sale_price);
        total += m.subtotal;
    });
    form.value.total_amount = total;
};

function aplicarPrecioSugerido(med) {
    const product = props.medicamentsList.find(
        (m) => String(m.id) === String(med.id),
    );
    const precio = product?.reference_sale_price;
    med.sale_price =
        precio != null && precio !== "" ? Number(precio) : 0;
    updateTotal();
}

// Agregar/quitar medicamento
const addMed = () => {
    form.value.medicaments.push({
        id: "",
        quantity: 1,
        sale_price: 0,
        subtotal: 0,
    });
    updateTotal();
};
const removeMed = (i) => {
    form.value.medicaments.splice(i, 1);
    updateTotal();
};

// Cancelar y volver al listado
const cancel = () => {
    router.get(route("notas-venta.index"));
};

// Enviar formulario con axios
const submit = async () => {
    form.value.processing = true;
    try {
        const { data } = await axios.post(
            route("notas-venta.store"),
            form.value,
            {
                headers: { Accept: "application/json" },
            }
        );
        toastType.value = "success";
        toastMsg.value = data.message || "Venta registrada correctamente";
        showToast.value = true;
        setTimeout(() => router.get(route("notas-venta.index")), 900);
    } catch (e) {
        toastType.value = "danger";
        toastMsg.value =
            e.response?.data?.message || "Error al registrar venta";
        showToast.value = true;
    } finally {
        form.value.processing = false;
    }
};
</script>

<template>
    <AdminLayout :title="actionTitle + ' Nota de Venta'">
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType">
                {{ toastMsg }}
            </FwbToast>
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
                        required
                        class="mt-1 block w-full p-3 border rounded-md focus:ring-2 focus:ring-emerald-500"
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
                    <div
                        class="grid grid-cols-1 md:grid-cols-4 gap-4 font-medium text-gray-700 mb-1"
                    >
                        <div>Medicamento</div>
                        <div class="text-right">Cantidad</div>
                        <div class="text-right">Precio venta (Bs)</div>
                        <div class="text-right">Subtotal</div>
                    </div>
                    <div
                        v-for="(med, i) in form.medicaments"
                        :key="i"
                        class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 items-center"
                    >
                        <!-- Select medicamento -->
                        <select
                            v-model="med.id"
                            @change="aplicarPrecioSugerido(med)"
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

                        <!-- Cantidad -->
                        <input
                            type="number"
                            v-model.number="med.quantity"
                            min="1"
                            placeholder="Cant."
                            @input="updateTotal"
                            required
                            class="block w-full p-3 border rounded-md focus:ring-2 focus:ring-emerald-500 text-right"
                        />

                        <!-- Precio venta -->
                        <input
                            type="number"
                            v-model.number="med.sale_price"
                            step="0.01"
                            min="0"
                            placeholder="Sugerido desde compra"
                            title="Precio sugerido según última nota de compra (editable)"
                            @input="updateTotal"
                            required
                            class="block w-full p-3 border rounded-md focus:ring-2 focus:ring-emerald-500 text-right bg-emerald-50"
                        />

                        <!-- Subtotal y eliminar -->
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
