<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import {
    FwbA,
    FwbButton,
    FwbTable,
    FwbTableBody,
    FwbTableCell,
    FwbTableHead,
    FwbTableHeadCell,
    FwbTableRow,
    FwbPagination,
} from "flowbite-vue";

// Props recibidas desde el controlador
const props = defineProps({
    warehouse: Object, // Almacén
    inventories: Array, // Inventarios agrupados por medicamento
});

const currentPage = ref(props.warehouse.current_page || 1);

// Watch para manejar la paginación
watch(currentPage, (newPage) => {
    router.get(
        route("almacenes.show", props.warehouse.id),
        { page: newPage },
        { preserveState: true }
    );
});
</script>

<template>
    <AdminLayout :title="`Inventarios de ${props.warehouse.name}`">
        <div class="flex justify-between my-6">
            <h2 class="text-2xl font-semibold vet-page-title">
                <i class="fa-solid fa-warehouse mr-1"></i>
                Almacén: {{ props.warehouse.name }}
            </h2>
            <FwbButton
                :href="route('almacenes.index')"
                type="button"
                color="yellow"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="inline w-4 h-4 mr-2"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 19l-7-7 7-7"
                    />
                </svg>
                Volver a Almacenes
            </FwbButton>
        </div>

        <div v-if="props.inventories && props.inventories.length > 0">
            <FwbTable>
                <FwbTableHead>
                    <FwbTableHeadCell>Medicamento</FwbTableHeadCell>
                    <FwbTableHeadCell>Total Stock</FwbTableHeadCell>
                    <FwbTableHeadCell>
                        <span class="sr-only">Ver</span>
                    </FwbTableHeadCell>
                </FwbTableHead>
                <FwbTableBody>
                    <FwbTableRow
                        v-for="(inventory, index) in props.inventories"
                        :key="index"
                    >
                        <FwbTableCell>{{
                            inventory.medicament.name
                        }}</FwbTableCell>
                        <FwbTableCell>{{ inventory.total_stock }}</FwbTableCell>
                        <FwbTableCell>
                            <FwbA
                                :href="
                                    route('almacenes.inventario', {
                                        warehouseId: props.warehouse.id,
                                        medicamentId: inventory.medicament.id,
                                    })
                                "
                            >
                                <i
                                    class="fa-solid fa-eye lg:mr-2 text-black hover:text-blue-600"
                                />
                            </FwbA>
                        </FwbTableCell>
                    </FwbTableRow>
                </FwbTableBody>
            </FwbTable>
        </div>

        <div v-else>
            <p>No hay medicamentos para este almacén.</p>
        </div>

        <!-- Paginación -->
        <div class="flex justify-center my-4">
            <FwbPagination
                v-model="currentPage"
                :total-items="props.warehouse.total"
                :per-page="props.warehouse.per_page"
                large
            ></FwbPagination>
        </div>
    </AdminLayout>
</template>
