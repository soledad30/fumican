<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { usePage, router, Link } from "@inertiajs/vue3";
import {
    FwbButton,
    FwbPagination,
    FwbTable,
    FwbTableBody,
    FwbTableCell,
    FwbTableHead,
    FwbTableHeadCell,
    FwbTableRow,
    FwbModal,
    FwbToast,
    FwbSpinner,
} from "flowbite-vue";
import { computed, ref, watch } from "vue";
import axios from "axios";
import { usePermisos } from "@/Composables/usePermisos";
import { useDebouncedRef } from "@/Utils/debouncedRef";

// Components
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import FormSectionTitle from "@/Components/Forms/FormSectionTitle.vue";
import SearchModal from "@/Components/Modals/SearchModal.vue";

// --- PROPS & FILTERS ---
const props = defineProps({ pets: Object, filters: Object });

const filters = ref({ search_term: props.filters?.search_term || "" });
function applyFilters() {
    router.get(route("mascotas.search"), filters.value, {
        preserveState: true,
        replace: true,
    });
}
function resetFilters() {
    filters.value = { search_term: "" };
    router.get(route("mascotas.index"));
}

// --- PAGINATION ---
const currentPage = ref(props.pets.current_page || 1);
watch(currentPage, (newPage) => {
    router.get(
        route("mascotas.search"),
        { ...filters.value, page: newPage },
        { preserveState: true, replace: true }
    );
});

// --- STATE MANAGEMENT ---
const loading = ref(false);
const showToast = ref(false);
const toastMsg = ref("");
const toastType = ref("success");

// --- MODALS ---
const modalMode = ref("create");
const isCreateOrEditModal = ref(false);
const isViewModal = ref(false);
const isDeleteModal = ref(false);
const isSearchOwnerModal = ref(false);
const selectedPet = ref(null);

// --- FORM & RELATED DATA ---
const defaultFormState = {
    id: null,
    name: "",
    customer_id: "",
    specie_id: "",
    breed_id: "",
    color: "",
    age: "",
    photo_url: "",
};
const form = ref({ ...defaultFormState });
const formErrors = ref({});
const owner = ref(null);
const photoFile = ref(null);
const photoPreview = ref(null);
const allSpecies = ref([]);
const breedsForSpecie = ref([]);
const isFetchingSpecies = ref(false);
const isFetchingBreeds = ref(false);
const specieMode = ref("list");
const breedMode = ref("list");
const customSpecie = ref("");
const customBreed = ref("");
const specieSelect = ref("");
const breedSelect = ref("");

// --- OWNER SEARCH ---
const ownerSearch = useDebouncedRef("", 400);
const isFetchingOwner = ref(false);
const customersList = ref([]);

// --- HELPERS & PERMISSIONS ---
const isEmptyData = computed(() => !props.pets?.data?.length);
const page = usePage();
const { puede } = usePermisos();
const canCreatePets = computed(() => puede("crear mascotas"));
const canEditPets = computed(() => puede("editar mascotas"));
const canDeletePets = computed(() => puede("eliminar mascotas"));

const selectedSpecieName = computed(() => {
    if (specieMode.value === "otro") {
        return customSpecie.value.trim() || "Otra especie";
    }
    return (
        allSpecies.value.find((s) => String(s.id) === String(form.value.specie_id))
            ?.name ?? ""
    );
});

// --- FUNCTIONS ---
function displayToast(type, message) {
    toastType.value = type;
    toastMsg.value = message;
    showToast.value = true;
    setTimeout(() => (showToast.value = false), 3000);
}

function resetSpecieBreedState() {
    form.value.specie_id = "";
    form.value.breed_id = "";
    specieMode.value = "list";
    breedMode.value = "list";
    customSpecie.value = "";
    customBreed.value = "";
    specieSelect.value = "";
    breedSelect.value = "";
    breedsForSpecie.value = [];
}

async function loadSpecies() {
    isFetchingSpecies.value = true;
    try {
        const response = await axios.get(route("especies.search"), {
            params: { search: "" },
        });
        allSpecies.value = response.data;
    } catch (error) {
        console.error("Error al cargar especies:", error);
        displayToast("danger", "No se pudieron cargar las especies.");
        allSpecies.value = [];
    } finally {
        isFetchingSpecies.value = false;
    }
}

async function fetchBreedsForSpecie(specieId) {
    if (!specieId) {
        breedsForSpecie.value = [];
        return;
    }
    isFetchingBreeds.value = true;
    try {
        const response = await axios.get(route("razas.search"), {
            params: { search: "", specie_id: specieId },
        });
        breedsForSpecie.value = response.data;
    } catch (error) {
        console.error("Error al cargar razas:", error);
        displayToast("danger", "No se pudieron cargar las razas.");
        breedsForSpecie.value = [];
    } finally {
        isFetchingBreeds.value = false;
    }
}

function onSpecieSelectChange() {
    if (specieSelect.value === "otro") {
        specieMode.value = "otro";
        form.value.specie_id = "";
        form.value.breed_id = "";
        breedMode.value = "otro";
        breedSelect.value = "otro";
        breedsForSpecie.value = [];
        customBreed.value = "";
        return;
    }

    specieMode.value = "list";
    form.value.specie_id = specieSelect.value;
    form.value.breed_id = "";
    breedMode.value = "list";
    breedSelect.value = "";
    customSpecie.value = "";
    customBreed.value = "";
    fetchBreedsForSpecie(specieSelect.value);
}

function onBreedSelectChange() {
    if (breedSelect.value === "otro") {
        breedMode.value = "otro";
        form.value.breed_id = "";
        return;
    }

    breedMode.value = "list";
    form.value.breed_id = breedSelect.value;
    customBreed.value = "";
}

function openCreateModal() {
    modalMode.value = "create";
    form.value = { ...defaultFormState };
    owner.value = null;
    photoFile.value = null;
    photoPreview.value = null;
    formErrors.value = {};
    resetSpecieBreedState();
    isCreateOrEditModal.value = true;
    loadSpecies();
}

async function openEditModal(pet) {
    modalMode.value = "edit";
    selectedPet.value = pet;
    form.value = { ...defaultFormState, ...pet };
    owner.value = pet.owner;
    photoFile.value = null;
    photoPreview.value = pet.photo_url || null;
    formErrors.value = {};
    resetSpecieBreedState();
    isCreateOrEditModal.value = true;

    await loadSpecies();

    const specieId = pet.breed?.specie?.id ?? pet.breed?.specie_id;
    if (specieId) {
        specieMode.value = "list";
        form.value.specie_id = specieId;
        specieSelect.value = String(specieId);
        await fetchBreedsForSpecie(specieId);
        if (pet.breed_id) {
            breedMode.value = "list";
            form.value.breed_id = pet.breed_id;
            breedSelect.value = String(pet.breed_id);
        }
    }
}

function onPhotoSelected(event) {
    const file = event.target.files?.[0];
    if (!file) {
        return;
    }
    photoFile.value = file;
    photoPreview.value = URL.createObjectURL(file);
}

function buildPetFormData() {
    const fd = new FormData();
    fd.append("name", form.value.name ?? "");
    fd.append("color", form.value.color ?? "");
    if (form.value.age !== "" && form.value.age != null) {
        fd.append("age", form.value.age);
    }
    fd.append("breed_id", form.value.breed_id ?? "");
    fd.append("customer_id", form.value.customer_id ?? "");
    if (photoFile.value) {
        fd.append("photo", photoFile.value);
    }
    return fd;
}

function openViewModal(pet) {
    selectedPet.value = pet;
    isViewModal.value = true;
}

function openDeleteModal(pet) {
    selectedPet.value = pet;
    isDeleteModal.value = true;
}

function closeAllModals() {
    isCreateOrEditModal.value = false;
    isViewModal.value = false;
    isDeleteModal.value = false;
    isSearchOwnerModal.value = false;
}

// --- Dynamic Specie/Breed/Owner Logic ---
async function fetchOwners(term = "") {
    isFetchingOwner.value = true;
    try {
        const response = await axios.get(route("clientes.autocomplete"), {
            params: { search: term },
        });
        customersList.value = response.data;
    } catch (error) {
        console.error("Error al buscar propietarios:", error);
        displayToast("danger", "No se pudieron cargar los propietarios.");
        customersList.value = [];
    } finally {
        isFetchingOwner.value = false;
    }
}

function openOwnerSearchModal() {
    isSearchOwnerModal.value = true;
    fetchOwners(ownerSearch.value);
}

watch(ownerSearch, (value) => {
    fetchOwners(value);
});

function selectOwner(customer) {
    owner.value = customer;
    form.value.customer_id = customer.id;
    isSearchOwnerModal.value = false;
    ownerSearch.value = "";
    customersList.value = [];
}

async function resolveBreedBeforeSave() {
    if (form.value.breed_id) {
        return true;
    }

    const specieName =
        specieMode.value === "otro"
            ? customSpecie.value.trim()
            : allSpecies.value.find(
                  (s) => String(s.id) === String(form.value.specie_id)
              )?.name ?? "";

    const breedName =
        breedMode.value === "otro"
            ? customBreed.value.trim()
            : breedsForSpecie.value.find(
                  (b) => String(b.id) === String(form.value.breed_id)
              )?.name ?? customBreed.value.trim();

    if (!specieName || !breedName) {
        displayToast("danger", "Seleccione especie y raza, o use «Otra» para registrarlas.");
        return false;
    }

    const prepResponse = await axios.post(route("mascotas.preparar-datos"), {
        breed: breedName,
        specie: specieName,
    });
    form.value.breed_id = prepResponse.data.breed_id;

    return true;
}

// --- CRUD ---
// CORREGIDO: Se usa axios para capturar la respuesta JSON del backend y mostrar el mensaje dinámico en el toast.
async function submitForm() {
    loading.value = true;
    formErrors.value = {};

    try {
        if (!(await resolveBreedBeforeSave())) {
            loading.value = false;
            return;
        }

        let response;
        const formData = buildPetFormData();
        if (modalMode.value === "edit") {
            response = await axios.put(
                route("mascotas.update", selectedPet.value.id),
                formData,
                { headers: { "Content-Type": "multipart/form-data" } }
            );
        } else {
            response = await axios.post(route("mascotas.store"), formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });
        }

        // Usar el mensaje dinámico de la respuesta del backend
        displayToast("success", response.data.message);

        closeAllModals();
        router.reload({ only: ["pets"] });
    } catch (e) {
        if (e.response?.status === 422) {
            formErrors.value = e.response.data.errors;
            // Usar un mensaje de error genérico o el del backend si lo hubiera
            const errorMessage =
                e.response.data.message || "Por favor, corrige los errores.";
            displayToast("danger", errorMessage);
        } else {
            // Mensaje para otros errores (ej. 500, error de red)
            displayToast("danger", "Ocurrió un error inesperado.");
        }
    } finally {
        loading.value = false;
    }
}

// CORREGIDO: Se usa axios para consistencia y para capturar el mensaje del backend.
async function submitDelete() {
    loading.value = true;
    try {
        const response = await axios.delete(
            route("mascotas.destroy", selectedPet.value.id)
        );

        // Usar el mensaje dinámico de la respuesta del backend
        displayToast("success", response.data.message);

        closeAllModals();
        router.reload({ only: ["pets"] });
    } catch (e) {
        const errorMessage =
            e.response?.data?.message || "Error al eliminar la mascota.";
        displayToast("danger", errorMessage);
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <AdminLayout title="Mascotas">
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType" closable>{{
                toastMsg
            }}</FwbToast>
        </div>

        <div class="flex justify-between my-6 items-center">
            <h2 class="text-2xl font-semibold vet-page-title">Mascotas</h2>
            <FwbButton
                v-if="canCreatePets"
                @click="openCreateModal"
                color="green"
                ><i class="fa-solid fa-plus mr-2"></i>Agregar Mascota</FwbButton
            >
        </div>

        <form
            @submit.prevent="applyFilters"
            class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 vet-filter-panel"
        >
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700"
                    >Buscar por Nombre o Propietario</label
                >
                <TextInput
                    v-model="filters.search_term"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="Ej: Fido, John Doe..."
                />
            </div>
            <div class="flex items-end space-x-2">
                <FwbButton color="green" type="submit">Filtrar</FwbButton>
                <FwbButton color="alternative" @click.prevent="resetFilters"
                    >Limpiar</FwbButton
                >
            </div>
        </form>

        <div class="vet-panel p-0 overflow-hidden">
        <FwbTable class="vet-list-table">
            <FwbTableHead>
                <FwbTableHeadCell>Nombre</FwbTableHeadCell>
                <FwbTableHeadCell>Propietario</FwbTableHeadCell>
                <FwbTableHeadCell>Especie</FwbTableHeadCell>
                <FwbTableHeadCell>Raza</FwbTableHeadCell>
                <FwbTableHeadCell>Actualizado</FwbTableHeadCell>
                <FwbTableHeadCell
                    ><span class="sr-only">Acciones</span></FwbTableHeadCell
                >
            </FwbTableHead>
            <FwbTableBody>
                <FwbTableRow v-if="isEmptyData"
                    ><FwbTableCell
                        colspan="6"
                        class="text-center py-4 text-gray-500"
                        >No se encontraron mascotas.</FwbTableCell
                    ></FwbTableRow
                >
                <FwbTableRow v-for="pet in pets.data" :key="pet.id">
                    <FwbTableCell>{{ pet.name }}</FwbTableCell>
                    <FwbTableCell
                        >{{ pet.owner?.first_name }}
                        {{ pet.owner?.last_name }}</FwbTableCell
                    >
                    <FwbTableCell>{{ pet.breed?.specie?.name }}</FwbTableCell>
                    <FwbTableCell>{{ pet.breed?.name }}</FwbTableCell>
                    <FwbTableCell>{{ pet.updated_at }}</FwbTableCell>
                    <FwbTableCell>
                        <div class="vet-table-actions">
                            <button
                                type="button"
                                class="vet-action-btn vet-action-btn--view"
                                title="Ver detalles"
                                @click="openViewModal(pet)"
                            >
                                <i class="fa-solid fa-eye fa-lg"></i>
                            </button>
                            <button
                                v-if="canEditPets"
                                type="button"
                                class="vet-action-btn vet-action-btn--edit"
                                title="Editar"
                                @click="openEditModal(pet)"
                            >
                                <i class="fa-solid fa-pencil fa-lg"></i>
                            </button>
                            <Link
                                :href="route('mascotas.historial', pet.id)"
                                class="vet-action-btn vet-action-btn--view"
                                title="Historial clínico"
                            >
                                <i class="fa-solid fa-clock-rotate-left fa-lg"></i>
                            </Link>
                            <button
                                v-if="canDeletePets"
                                type="button"
                                class="vet-action-btn vet-action-btn--delete"
                                title="Eliminar"
                                @click="openDeleteModal(pet)"
                            >
                                <i class="fa-solid fa-trash fa-lg"></i>
                            </button>
                            <a
                                :href="route('consultas-medicas.historial-reporte', { pet: pet.id })"
                                target="_blank"
                                class="vet-action-btn vet-action-btn--delete"
                                title="PDF historial"
                            >
                                <i class="fa-solid fa-file-pdf fa-lg"></i>
                            </a>
                        </div>
                    </FwbTableCell>
                </FwbTableRow>
            </FwbTableBody>
        </FwbTable>
        </div>
        <div v-if="!isEmptyData" class="flex justify-center my-4">
            <FwbPagination
                v-model="currentPage"
                :total-items="pets.total"
                :per-page="pets.per_page"
                large
            />
        </div>

        <FwbModal size="lg" v-if="isViewModal" @close="closeAllModals">
            <template #header
                ><h3 class="text-xl font-semibold">
                    Detalles de la Mascota
                </h3></template
            >
            <template #body>
                <div v-if="selectedPet" class="space-y-4 text-sm">
                    <p><strong>Nombre:</strong> {{ selectedPet.name }}</p>
                    <p>
                        <strong>Propietario:</strong>
                        {{ selectedPet.owner?.first_name }}
                        {{ selectedPet.owner?.last_name }}
                    </p>
                    <p>
                        <strong>Especie:</strong>
                        {{ selectedPet.breed?.specie?.name }}
                    </p>
                    <p><strong>Raza:</strong> {{ selectedPet.breed?.name }}</p>
                    <p><strong>Color:</strong> {{ selectedPet.color }}</p>
                    <p><strong>Edad:</strong> {{ selectedPet.age ?? "—" }}</p>
                    <div v-if="selectedPet.photo_url" class="pt-2">
                        <strong>Foto:</strong>
                        <img
                            :src="selectedPet.photo_url"
                            alt="Foto de la mascota"
                            class="mt-2 h-32 w-32 rounded-lg object-cover border"
                        />
                    </div>
                </div>
            </template>
            <template #footer
                ><FwbButton @click="closeAllModals" color="alternative"
                    >Cerrar</FwbButton
                ></template
            >
        </FwbModal>

        <FwbModal v-if="isDeleteModal" @close="closeAllModals">
            <template #header>Confirmar Eliminación</template>
            <template #body
                ><p class="text-center text-lg">
                    ¿Seguro que deseas eliminar a
                    <strong>{{ selectedPet?.name }}</strong
                    >?
                </p></template
            >
            <template #footer>
                <div class="flex justify-center w-full">
                    <FwbButton @click="closeAllModals" color="alternative"
                        >Cancelar</FwbButton
                    ><FwbButton
                        @click="submitDelete"
                        color="red"
                        :loading="loading"
                        class="ml-2"
                        >Eliminar</FwbButton
                    >
                </div>
            </template>
        </FwbModal>

        <FwbModal size="4xl" v-if="isCreateOrEditModal" @close="closeAllModals">
            <template #header
                ><h3 class="text-xl font-semibold">
                    {{ modalMode === "edit" ? "Editar" : "Registrar" }} Mascota
                </h3></template
            >
            <template #body>
                <form class="space-y-6" @submit.prevent="submitForm">
                    <div>
                        <div class="mb-4 flex justify-between items-start">
                            <h3 class="text-lg font-semibold text-gray-700">
                                Datos del Propietario
                            </h3>
                            <FwbButton
                                @click="openOwnerSearchModal"
                                type="button"
                                color="green"
                                size="xs"
                                >Buscar Propietario</FwbButton
                            >
                        </div>
                        <div
                            v-if="!owner"
                            class="border py-6 text-center text-gray-500 rounded-lg"
                            :class="{
                                'bg-red-50 border-red-300':
                                    formErrors.customer_id,
                            }"
                        >
                            Debe seleccionar un propietario
                            <InputError
                                class="mt-1"
                                :message="formErrors.customer_id?.[0]"
                            />
                        </div>
                        <div
                            v-else
                            class="grid grid-cols-2 gap-4 rounded bg-gray-50 border p-3 text-sm"
                        >
                            <p>
                                <strong>Nombre:</strong> {{ owner.first_name }}
                                {{ owner.last_name }}
                            </p>
                            <p><strong>CI:</strong> {{ owner.ci }}</p>
                        </div>
                    </div>
                    <FormSectionTitle title="Datos de la Mascota" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="name" value="Nombre" /><TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 w-full"
                            /><InputError
                                class="mt-1"
                                :message="formErrors.name?.[0]"
                            />
                        </div>
                        <div>
                            <InputLabel for="color" value="Color" /><TextInput
                                id="color"
                                v-model="form.color"
                                type="text"
                                class="mt-1 w-full"
                            /><InputError
                                class="mt-1"
                                :message="formErrors.color?.[0]"
                            />
                        </div>
                        <div>
                            <InputLabel for="specie_select" value="Especie" />
                            <div class="relative mt-1">
                                <select
                                    id="specie_select"
                                    v-model="specieSelect"
                                    class="pet-select w-full"
                                    :disabled="isFetchingSpecies"
                                    @change="onSpecieSelectChange"
                                >
                                    <option value="" disabled>
                                        {{
                                            isFetchingSpecies
                                                ? "Cargando especies..."
                                                : "Seleccione una especie"
                                        }}
                                    </option>
                                    <option
                                        v-for="s in allSpecies"
                                        :key="s.id"
                                        :value="String(s.id)"
                                    >
                                        {{ s.name }}
                                    </option>
                                    <option value="otro">Otra...</option>
                                </select>
                                <FwbSpinner
                                    v-if="isFetchingSpecies"
                                    size="4"
                                    class="pet-select-spinner"
                                />
                            </div>
                            <TextInput
                                v-if="specieMode === 'otro'"
                                v-model="customSpecie"
                                type="text"
                                class="mt-2 w-full"
                                placeholder="Ej: Conejo, Reptil, Roedor..."
                            />
                            <InputError
                                class="mt-1"
                                :message="formErrors.specie_id?.[0]"
                            />
                        </div>

                        <div
                            v-if="
                                specieSelect &&
                                specieSelect !== 'otro'
                            "
                        >
                            <InputLabel
                                for="breed_select"
                                :value="`Raza (${selectedSpecieName})`"
                            />
                            <div class="relative mt-1">
                                <select
                                    id="breed_select"
                                    v-model="breedSelect"
                                    class="pet-select w-full"
                                    :disabled="isFetchingBreeds"
                                    @change="onBreedSelectChange"
                                >
                                    <option value="" disabled>
                                        {{
                                            isFetchingBreeds
                                                ? "Cargando razas..."
                                                : "Seleccione una raza"
                                        }}
                                    </option>
                                    <option
                                        v-for="b in breedsForSpecie"
                                        :key="b.id"
                                        :value="String(b.id)"
                                    >
                                        {{ b.name }}
                                    </option>
                                    <option value="otro">Otra...</option>
                                </select>
                                <FwbSpinner
                                    v-if="isFetchingBreeds"
                                    size="4"
                                    class="pet-select-spinner"
                                />
                            </div>
                            <TextInput
                                v-if="breedMode === 'otro'"
                                v-model="customBreed"
                                type="text"
                                class="mt-2 w-full"
                                placeholder="Ej: Labrador, Caniche, Mestizo..."
                            />
                            <InputError
                                class="mt-1"
                                :message="formErrors.breed_id?.[0]"
                            />
                        </div>

                        <div v-else-if="specieSelect === 'otro'">
                            <InputLabel
                                for="custom_breed"
                                value="Raza (otra especie)"
                            />
                            <TextInput
                                id="custom_breed"
                                v-model="customBreed"
                                type="text"
                                class="mt-1 w-full"
                                placeholder="Ej: Angora, Mestizo..."
                            />
                            <InputError
                                class="mt-1"
                                :message="formErrors.breed_id?.[0]"
                            />
                        </div>
                        <div>
                            <InputLabel for="age" value="Edad" /><TextInput
                                id="age"
                                v-model="form.age"
                                type="text"
                                class="mt-1 w-full"
                            /><InputError
                                class="mt-1"
                                :message="formErrors.age?.[0]"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <InputLabel value="Foto de la mascota (opcional)" />
                            <div class="mt-2 flex items-start gap-4">
                                <div
                                    v-if="photoPreview"
                                    class="shrink-0"
                                >
                                    <img
                                        :src="photoPreview"
                                        alt="Vista previa"
                                        class="h-24 w-24 rounded-lg object-cover border"
                                    />
                                </div>
                                <div class="flex-1">
                                    <input
                                        type="file"
                                        accept="image/*"
                                        class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
                                        @change="onPhotoSelected"
                                    />
                                    
                                    <InputError
                                        class="mt-1"
                                        :message="formErrors.photo?.[0]"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </template>
            <template #footer>
                <div class="flex justify-end">
                    <FwbButton @click="closeAllModals" color="alternative"
                        >Cancelar</FwbButton
                    ><FwbButton
                        @click="submitForm"
                        color="green"
                        :loading="loading"
                        class="ml-2"
                        >Guardar</FwbButton
                    >
                </div>
            </template>
        </FwbModal>

        <SearchModal
            v-if="isSearchOwnerModal"
            nested
            layout="table"
            hint="Seleccione un propietario de la lista o filtre por nombre o CI."
            @close="isSearchOwnerModal = false"
            :search="ownerSearch"
            @update:search="ownerSearch = $event"
            :isFetchingData="isFetchingOwner"
            :results="customersList"
            @select="selectOwner"
            title="Buscar Propietario"
            placeholder="Nombre o CI del propietario..."
        >
            <template #thead>
                <tr>
                    <th>Propietario</th>
                    <th>CI</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                    <th></th>
                </tr>
            </template>
            <template #result="{ result }">
                <td>
                    <div class="font-semibold">
                        {{ result.first_name }} {{ result.last_name }}
                    </div>
                </td>
                <td>{{ result.ci || "—" }}</td>
                <td>{{ result.phone_number || "—" }}</td>
                <td
                    class="max-w-[10rem] truncate"
                    :title="result.address || ''"
                >
                    {{ result.address || "—" }}
                </td>
                <td class="text-right whitespace-nowrap">
                    <span class="search-modal-select-btn">
                        <i class="fa-solid fa-check text-[10px]"></i>
                        Seleccionar
                    </span>
                </td>
            </template>
        </SearchModal>
    </AdminLayout>
</template>

<style scoped>
.pet-select {
    display: block;
    width: 100%;
    padding: 0.5rem 2.25rem 0.5rem 0.75rem;
    border-radius: 0.375rem;
    border: 1px solid var(--color-border);
    background-color: var(--color-surface);
    color: var(--color-text-base);
    font-size: 0.875rem;
    line-height: 1.25rem;
    box-shadow: 0 1px 2px color-mix(in srgb, var(--color-text-base) 6%, transparent);
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.25em 1.25em;
}

.pet-select:disabled {
    opacity: 0.65;
    cursor: not-allowed;
    background-color: var(--color-surface-hover);
}

.pet-select:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-primary) 25%, transparent);
}

.pet-select-spinner {
    position: absolute;
    right: 2rem;
    top: 50%;
    transform: translateY(-50%);
}
</style>
