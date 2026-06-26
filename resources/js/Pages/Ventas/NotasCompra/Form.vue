<script setup>
import { ref, computed } from "vue";
import axios from "axios";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { router } from "@inertiajs/vue3";
import { FwbToast, FwbButton } from "flowbite-vue";

const props = defineProps({
    suppliers: Array,
    warehouses: Array,
    medicamentsList: Array,
    pricingConfig: {
        type: Object,
        default: () => ({ iva_porcentaje: 0.13, margen_default: 0.3 }),
    },
});

const form = ref({
    supplier_id: "",
    warehouse_id: "",
    medicaments: [{
        id: "",
        quantity: 1,
        purchase_price: 0,
        sale_price: 0,
        expiration_date: "",
        subtotal: 0,
    }],
    total_amount: 0,
    processing: false,
});

const showToast = ref(false);
const toastMsg = ref("");
const toastType = ref("success");
const actionTitle = computed(() => "Agregar");

const margenPct = computed(() => Math.round((props.pricingConfig.margen_default ?? 0.3) * 100));
const ivaPct = computed(() => Math.round((props.pricingConfig.iva_porcentaje ?? 0.13) * 100));

function calcSalePrice(purchasePrice) {
    const margen = props.pricingConfig.margen_default ?? 0.3;
    const iva = props.pricingConfig.iva_porcentaje ?? 0.13;
    const base = Number(purchasePrice) * (1 + margen);
    return Math.round(base * (1 + iva) * 100) / 100;
}

function updateLine(med) {
    med.subtotal = Number(med.quantity) * Number(med.purchase_price);
    med.sale_price = calcSalePrice(med.purchase_price);
    updateTotal();
}

const updateTotal = () => {
    let total = 0;
    form.value.medicaments.forEach((m) => {
        m.subtotal = Number(m.quantity) * Number(m.purchase_price);
        total += m.subtotal;
    });
    form.value.total_amount = total;
};

const addMed = () => {
    form.value.medicaments.push({
        id: "",
        quantity: 1,
        purchase_price: 0,
        sale_price: 0,
        expiration_date: "",
        subtotal: 0,
    });
    updateTotal();
};

const removeMed = (i) => {
    form.value.medicaments.splice(i, 1);
    updateTotal();
};

const cancel = () => {
    router.get(route("notas-compra.index"));
};

const submit = async () => {
    form.value.processing = true;
    try {
        const { data } = await axios.post(route("notas-compra.store"), form.value);
        toastType.value = "success";
        toastMsg.value = data.message;
        showToast.value = true;
        setTimeout(() => router.get(route("notas-compra.index")), 800);
    } catch (e) {
        toastType.value = "danger";
        toastMsg.value = e.response?.data?.error || e.response?.data?.message || "Error al crear nota";
        showToast.value = true;
    } finally {
        form.value.processing = false;
    }
};
</script>

<template>
    <AdminLayout :title="actionTitle + ' Nota de Compra'">
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType">{{ toastMsg }}</FwbToast>
        </div>

        <div class="container mx-auto p-6">
            <h2 class="text-2xl font-semibold vet-page-title mb-2">{{ actionTitle }} Nota de Compra</h2>
            <p class="text-sm text-gray-500 mb-4">
                Precio venta sugerido: costo + {{ margenPct }}% margen + {{ ivaPct }}% IVA (editable por línea).
            </p>
            <form @submit.prevent="submit">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-600">Proveedor</label>
                    <select v-model="form.supplier_id" class="mt-1 block w-full p-3 border rounded-md" required>
                        <option value="">Seleccionar</option>
                        <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-600">Almacén</label>
                    <select v-model="form.warehouse_id" class="mt-1 block w-full p-3 border rounded-md" required>
                        <option value="">Seleccionar</option>
                        <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                    </select>
                </div>

                <div class="my-6">
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">Productos</h3>
                    <div class="hidden md:grid md:grid-cols-6 gap-2 font-medium text-gray-700 mb-1 text-sm">
                        <div>Producto</div>
                        <div class="text-right">Cant.</div>
                        <div class="text-right">P. compra</div>
                        <div class="text-right">P. venta</div>
                        <div>Vencimiento</div>
                        <div class="text-right">Subtotal</div>
                    </div>

                    <div
                        v-for="(med, i) in form.medicaments"
                        :key="i"
                        class="grid grid-cols-1 md:grid-cols-6 gap-3 mb-4 items-center border-b pb-4 md:border-0 md:pb-0"
                    >
                        <select
                            v-model="med.id"
                            class="block w-full p-3 border rounded-md"
                            required
                        >
                            <option value="">Seleccionar</option>
                            <option v-for="m in medicamentsList" :key="m.id" :value="m.id">{{ m.name }}</option>
                        </select>
                        <input
                            type="number"
                            v-model.number="med.quantity"
                            min="1"
                            @input="updateLine(med)"
                            class="block w-full p-3 border rounded-md text-right"
                        />
                        <input
                            type="number"
                            v-model.number="med.purchase_price"
                            step="0.01"
                            min="0"
                            @input="updateLine(med)"
                            class="block w-full p-3 border rounded-md text-right"
                        />
                        <input
                            type="number"
                            v-model.number="med.sale_price"
                            step="0.01"
                            min="0"
                            class="block w-full p-3 border rounded-md text-right bg-emerald-50"
                            title="Precio de venta sugerido (editable)"
                        />
                        <input
                            type="date"
                            v-model="med.expiration_date"
                            class="block w-full p-3 border rounded-md"
                        />
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-semibold">{{ Number(med.subtotal).toFixed(2) }}</span>
                            <button type="button" @click="removeMed(i)" class="text-red-600 hover:text-red-800">&times;</button>
                        </div>
                    </div>

                    <button type="button" @click="addMed" class="text-green-600 hover:text-green-800 font-medium">
                        + Agregar producto
                    </button>
                </div>

                <div class="my-6">
                    <label class="block text-sm font-medium text-gray-600">Total General</label>
                    <input
                        type="text"
                        :value="form.total_amount.toFixed(2)"
                        readonly
                        class="mt-1 block w-full p-3 border rounded-md bg-gray-100 text-right"
                    />
                </div>

                <div class="flex justify-end space-x-4">
                    <FwbButton color="alternative" @click="cancel">Cancelar</FwbButton>
                    <FwbButton type="submit" :disabled="form.processing">{{ actionTitle }}</FwbButton>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<style scoped>
.container {
    max-width: 1100px;
    margin: 0 auto;
}
</style>
