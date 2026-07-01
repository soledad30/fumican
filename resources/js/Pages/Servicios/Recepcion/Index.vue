<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { router } from "@inertiajs/vue3";
import { FwbButton, FwbRadio, FwbSpinner, FwbToast } from "flowbite-vue";
import { computed, onMounted, ref, watch } from "vue";
import axios from "axios";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import FormSectionTitle from "@/Components/Forms/FormSectionTitle.vue";

const props = defineProps({
    especies: Array,
    clientesSinUsuario: { type: Array, default: () => [] },
});

const step = ref(1);
const loading = ref(false);
const showToast = ref(false);
const toastMsg = ref("");
const toastType = ref("success");
const formErrors = ref({});

const modoCliente = ref("nuevo");
const modoMascota = ref("nueva");
const clienteId = ref(null);
const mascotaId = ref(null);
const clienteTieneUsuario = ref(false);

const clienteSeleccionadoId = ref("");
const mascotaSeleccionadaId = ref("");
const clienteBusqueda = ref("");
const clientesOpciones = ref([]);
const mascotasCliente = ref([]);
const clientesSinUsuarioLista = ref([...props.clientesSinUsuario]);

const clienteUsuarioId = ref("");

const cliente = ref({
    first_name: "",
    last_name: "",
    ci: "",
    phone_number: "",
    gender: "0",
    birthdate: "",
    address: "",
    email: "",
});

const mascota = ref({
    name: "",
    color: "",
    age: "",
    customer_id: null,
    specie_id: "",
    breed_id: "",
});

const photoFile = ref(null);
const photoPreview = ref(null);

const usuario = ref({
    crear_cuenta: true,
    email: "",
});

const allSpecies = ref([]);
const breedsForSpecie = ref([]);
const isFetchingBreeds = ref(false);
const isFetchingClientes = ref(false);
const clienteListo = ref(false);
const clienteSnapshot = ref(null);
let buscarClientesTimeout = null;
const specieMode = ref("list");
const breedMode = ref("list");
const customSpecie = ref("");
const customBreed = ref("");
const specieSelect = ref("");
const breedSelect = ref("");

const hayMascotasRegistradas = computed(() => mascotasCliente.value.length > 0);

const mostrarFormularioCliente = computed(
    () =>
        modoCliente.value === "nuevo" ||
        (modoCliente.value === "existente" && clienteSeleccionadoId.value)
);

const mostrarFormularioMascota = computed(
    () =>
        modoMascota.value === "nueva" ||
        (modoMascota.value === "existente" && mascotaSeleccionadaId.value)
);

const clienteUsuarioSeleccionado = computed(() =>
    clientesSinUsuarioLista.value.find(
        (c) => String(c.id) === String(clienteUsuarioId.value)
    )
);

const selectedSpecieName = computed(() => {
    if (specieMode.value === "otro") {
        return customSpecie.value.trim() || "Otra especie";
    }
    return (
        allSpecies.value.find((s) => String(s.id) === String(mascota.value.specie_id))
            ?.name ?? ""
    );
});

onMounted(() => {
    allSpecies.value = props.especies ?? [];
    buscarClientes();
});

watch(modoCliente, (modo) => {
    if (modo === "nuevo") {
        clienteSeleccionadoId.value = "";
        clienteId.value = null;
        clienteListo.value = false;
        clienteSnapshot.value = null;
        clienteTieneUsuario.value = false;
        limpiarFormularioCliente();
    } else {
        clienteListo.value = false;
        clienteSnapshot.value = null;
        buscarClientes();
    }
});

watch(clienteBusqueda, () => {
    clearTimeout(buscarClientesTimeout);
    buscarClientesTimeout = setTimeout(() => buscarClientes(), 300);
});

watch(modoMascota, (modo) => {
    if (modo === "nueva") {
        mascotaSeleccionadaId.value = "";
        mascotaId.value = null;
        limpiarFormularioMascota();
    }
});

function limpiarFormularioCliente() {
    cliente.value = {
        first_name: "",
        last_name: "",
        ci: "",
        phone_number: "",
        gender: "0",
        birthdate: "",
        address: "",
        email: "",
    };
}

function limpiarFormularioMascota() {
    mascota.value = {
        name: "",
        color: "",
        age: "",
        customer_id: clienteId.value,
        specie_id: "",
        breed_id: "",
    };
    photoFile.value = null;
    photoPreview.value = null;
    resetSpecieBreedState();
}

function onPhotoSelected(event) {
    const file = event.target.files?.[0];
    if (!file) {
        return;
    }
    photoFile.value = file;
    photoPreview.value = URL.createObjectURL(file);
}

function buildMascotaFormData() {
    const fd = new FormData();
    fd.append("name", mascota.value.name ?? "");
    fd.append("color", mascota.value.color?.trim() || "Sin especificar");
    if (mascota.value.age !== "" && mascota.value.age != null) {
        fd.append("age", mascota.value.age);
    }
    fd.append("breed_id", mascota.value.breed_id ?? "");
    fd.append("customer_id", mascota.value.customer_id ?? clienteId.value ?? "");
    if (photoFile.value) {
        fd.append("photo", photoFile.value);
    }
    return fd;
}

function toast(type, msg) {
    toastType.value = type;
    toastMsg.value = msg;
    showToast.value = true;
    setTimeout(() => (showToast.value = false), 4000);
}

function resetSpecieBreedState() {
    mascota.value.specie_id = "";
    mascota.value.breed_id = "";
    specieMode.value = "list";
    breedMode.value = "list";
    customSpecie.value = "";
    customBreed.value = "";
    specieSelect.value = "";
    breedSelect.value = "";
    breedsForSpecie.value = [];
}

async function buscarClientes() {
    isFetchingClientes.value = true;
    try {
        const { data } = await axios.get(route("recepcion.clientes"), {
            params: { search: clienteBusqueda.value.trim() },
        });
        clientesOpciones.value = Array.isArray(data) ? data : [];
    } catch {
        clientesOpciones.value = [];
        toast("danger", "No se pudieron cargar los clientes.");
    } finally {
        isFetchingClientes.value = false;
    }
}

function etiquetaCliente(c) {
    const nombre = (
        c.full_name
        || [c.first_name || c.nombre, c.last_name || c.apellido].filter(Boolean).join(" ")
    ).trim();

    return `${nombre || "Sin nombre"} — CI ${c.ci || "—"}`;
}

function snapshotClienteActual() {
    return JSON.stringify(payloadCliente());
}

function clienteDatosModificados() {
    if (!clienteSnapshot.value) {
        return false;
    }

    return snapshotClienteActual() !== clienteSnapshot.value;
}

async function cargarClienteSeleccionado() {
    if (!clienteSeleccionadoId.value) {
        clienteListo.value = false;
        clienteSnapshot.value = null;
        return;
    }

    loading.value = true;
    clienteListo.value = false;
    try {
        const { data } = await axios.get(
            route("recepcion.clientes.show", clienteSeleccionadoId.value)
        );
        clienteId.value = data.id;
        clienteTieneUsuario.value = Boolean(data.usuario_id);
        cliente.value = {
            first_name: data.first_name ?? data.nombre ?? "",
            last_name: data.last_name ?? data.apellido ?? "",
            ci: data.ci ?? "",
            phone_number: data.phone_number ?? data.telefono ?? "",
            gender: String(data.gender ?? "0"),
            birthdate: fechaParaInput(data.birthdate ?? data.fecha_nacimiento),
            address: data.address ?? data.direccion ?? "",
            email: data.email ?? data.usuario?.email ?? "",
        };
        usuario.value.email = cliente.value.email;
        clienteUsuarioId.value = data.usuario_id ? "" : String(data.id);
        clienteSnapshot.value = snapshotClienteActual();
        clienteListo.value = true;
    } catch {
        clienteListo.value = false;
        clienteSnapshot.value = null;
        toast("danger", "No se pudo cargar el cliente.");
    } finally {
        loading.value = false;
    }
}

async function cargarMascotasCliente() {
    if (!clienteId.value) {
        mascotasCliente.value = [];
        return;
    }

    try {
        const { data } = await axios.get(
            route("recepcion.clientes.mascotas", clienteId.value)
        );
        mascotasCliente.value = data;
        if (data.length > 0 && modoMascota.value === "existente") {
            modoMascota.value = "existente";
        }
    } catch {
        mascotasCliente.value = [];
        toast("danger", "No se pudieron cargar las mascotas del cliente.");
    }
}

function fechaParaInput(valor) {
    if (!valor) {
        return "";
    }
    const str = String(valor);
    if (/^\d{4}-\d{2}-\d{2}/.test(str)) {
        return str.slice(0, 10);
    }
    const d = new Date(str);
    if (Number.isNaN(d.getTime())) {
        return "";
    }
    return d.toISOString().slice(0, 10);
}

async function cargarMascotaSeleccionada() {
    const pet = mascotasCliente.value.find(
        (m) => String(m.id) === String(mascotaSeleccionadaId.value)
    );
    if (!pet) {
        return;
    }

    const breedId = pet.breed_id ?? pet.raza_id ?? "";
    const specieId =
        pet.breed?.specie?.id ??
        pet.raza?.especie?.id ??
        pet.breed?.specie_id ??
        pet.raza?.especie_id ??
        "";

    resetSpecieBreedState();

    mascotaId.value = pet.id;
    mascota.value = {
        name: pet.name ?? pet.nombre ?? "",
        color: pet.color ?? "",
        age: pet.age ?? "",
        customer_id: clienteId.value,
        specie_id: specieId ? String(specieId) : "",
        breed_id: breedId ? String(breedId) : "",
    };
    photoFile.value = null;
    photoPreview.value = pet.photo_url ?? null;

    if (!specieId) {
        return;
    }

    specieMode.value = "list";
    specieSelect.value = String(specieId);
    await fetchBreedsForSpecie(specieId);

    if (breedId) {
        const raza = pet.breed ?? pet.raza;
        if (
            raza &&
            !breedsForSpecie.value.some((b) => String(b.id) === String(breedId))
        ) {
            breedsForSpecie.value = [...breedsForSpecie.value, raza];
        }
        breedMode.value = "list";
        breedSelect.value = String(breedId);
        mascota.value.breed_id = String(breedId);
    }
}

async function fetchBreedsForSpecie(specieId) {
    if (!specieId) {
        breedsForSpecie.value = [];
        return;
    }
    isFetchingBreeds.value = true;
    try {
        const { data } = await axios.get(route("recepcion.razas"), {
            params: { search: "", specie_id: specieId },
        });
        breedsForSpecie.value = data;
    } catch {
        toast("danger", "No se pudieron cargar las razas.");
        breedsForSpecie.value = [];
    } finally {
        isFetchingBreeds.value = false;
    }
}

function onSpecieSelectChange() {
    if (specieSelect.value === "otro") {
        specieMode.value = "otro";
        mascota.value.specie_id = "";
        mascota.value.breed_id = "";
        breedMode.value = "otro";
        breedSelect.value = "otro";
        breedsForSpecie.value = [];
        customBreed.value = "";
        return;
    }

    specieMode.value = "list";
    mascota.value.specie_id = specieSelect.value;
    mascota.value.breed_id = "";
    breedMode.value = "list";
    breedSelect.value = "";
    customSpecie.value = "";
    customBreed.value = "";
    fetchBreedsForSpecie(specieSelect.value);
}

function onBreedSelectChange() {
    if (breedSelect.value === "otro") {
        breedMode.value = "otro";
        mascota.value.breed_id = "";
        return;
    }

    breedMode.value = "list";
    mascota.value.breed_id = breedSelect.value;
    customBreed.value = "";
}

async function resolveBreedBeforeSave() {
    if (mascota.value.breed_id) {
        return true;
    }

    const specieName =
        specieMode.value === "otro"
            ? customSpecie.value.trim()
            : allSpecies.value.find(
                  (s) => String(s.id) === String(mascota.value.specie_id)
              )?.name ?? "";

    const breedName =
        breedMode.value === "otro"
            ? customBreed.value.trim()
            : breedsForSpecie.value.find(
                  (b) => String(b.id) === String(mascota.value.breed_id)
              )?.name ?? customBreed.value.trim();

    if (!specieName || !breedName) {
        toast("danger", "Seleccione especie y raza, o use «Otra» para registrarlas.");
        return false;
    }

    const { data } = await axios.post(route("recepcion.preparar-datos"), {
        breed: breedName,
        specie: specieName,
    });
    mascota.value.breed_id = data.breed_id;

    return true;
}

function payloadCliente() {
    const gender = Number.parseInt(String(cliente.value.gender ?? "0"), 10);

    return {
        first_name: cliente.value.first_name,
        last_name: cliente.value.last_name,
        ci: cliente.value.ci,
        phone_number: cliente.value.phone_number,
        gender: Number.isNaN(gender) ? 0 : gender,
        birthdate: cliente.value.birthdate || null,
        address: cliente.value.address || null,
        email: cliente.value.email || null,
    };
}

async function avanzarAMascota() {
    usuario.value.email = cliente.value.email;
    await cargarMascotasCliente();
    modoMascota.value = hayMascotasRegistradas.value ? "existente" : "nueva";
    step.value = 2;
}

async function guardarCliente() {
    loading.value = true;
    formErrors.value = {};

    try {
        if (modoCliente.value === "existente") {
            if (!clienteSeleccionadoId.value) {
                toast("danger", "Seleccione un cliente de la lista.");
                return;
            }

            if (!clienteListo.value) {
                await cargarClienteSeleccionado();
            }

            if (!clienteListo.value) {
                return;
            }

            if (clienteDatosModificados()) {
                const { data } = await axios.put(
                    route("recepcion.clientes.update", clienteSeleccionadoId.value),
                    payloadCliente()
                );
                clienteId.value = data.customer?.id ?? clienteSeleccionadoId.value;
                clienteSnapshot.value = snapshotClienteActual();
                toast("success", "Cliente actualizado.");
            }

            await avanzarAMascota();
            return;
        }

        const { data } = await axios.post(
            route("recepcion.clientes.store"),
            payloadCliente()
        );
        clienteId.value = data.customer?.id;
        clienteSeleccionadoId.value = String(clienteId.value);
        clienteUsuarioId.value = String(clienteId.value);
        clientesSinUsuarioLista.value = [
            data.customer,
            ...clientesSinUsuarioLista.value.filter(
                (c) => String(c.id) !== String(data.customer?.id)
            ),
        ];
        toast("success", "Cliente registrado.");
        await avanzarAMascota();
    } catch (e) {
        formErrors.value = e.response?.data?.errors ?? {};
        const mensaje =
            e.response?.data?.message
            || Object.values(e.response?.data?.errors ?? {}).flat()[0]
            || "Revise los datos del cliente.";
        toast("danger", mensaje);
    } finally {
        loading.value = false;
    }
}

async function guardarMascota() {
    loading.value = true;
    formErrors.value = {};
    mascota.value.customer_id = clienteId.value;

    try {
        if (modoMascota.value === "existente") {
            if (!mascotaSeleccionadaId.value) {
                toast("danger", "Seleccione una mascota de la lista.");
                loading.value = false;
                return;
            }

            if (!(await resolveBreedBeforeSave())) {
                loading.value = false;
                return;
            }

            const { data } = await axios.put(
                route("recepcion.mascotas.update", mascotaSeleccionadaId.value),
                buildMascotaFormData(),
                { headers: { "Content-Type": "multipart/form-data" } }
            );
            mascotaId.value = data.pet?.id ?? mascotaSeleccionadaId.value;
            toast("success", "Mascota actualizada.");
        } else {
            if (!(await resolveBreedBeforeSave())) {
                loading.value = false;
                return;
            }

            const { data } = await axios.post(
                route("recepcion.mascotas.store"),
                buildMascotaFormData(),
                { headers: { "Content-Type": "multipart/form-data" } }
            );
            mascotaId.value = data.pet?.id;
            toast("success", "Mascota registrada.");
        }

        step.value = 3;
    } catch (e) {
        formErrors.value = e.response?.data?.errors ?? {};
        toast("danger", "Revise los datos de la mascota.");
    } finally {
        loading.value = false;
    }
}

async function refrescarClientesSinUsuario() {
    try {
        const { data } = await axios.get(route("recepcion.clientes-sin-usuario"));
        clientesSinUsuarioLista.value = data;
    } catch {
        clientesSinUsuarioLista.value = props.clientesSinUsuario ?? [];
    }
}

async function guardarUsuario() {
    if (!usuario.value.crear_cuenta || clienteTieneUsuario.value) {
        step.value = 4;
        return;
    }

    const destinoId = clienteUsuarioId.value || clienteId.value;
    if (!destinoId) {
        toast("danger", "Seleccione el cliente para crear su acceso.");
        return;
    }

    const destino =
        clienteUsuarioSeleccionado.value ||
        clientesSinUsuarioLista.value.find((c) => String(c.id) === String(destinoId));

    loading.value = true;
    formErrors.value = {};

    try {
        await axios.post(route("recepcion.usuario.store"), {
            first_name: destino?.first_name ?? destino?.nombre ?? cliente.value.first_name,
            last_name: destino?.last_name ?? destino?.apellido ?? cliente.value.last_name,
            email: usuario.value.email || cliente.value.email,
            cliente_id: destinoId,
        });
        step.value = 4;
        clienteTieneUsuario.value = true;
        toast("success", "Usuario cliente creado. Ya puede ingresar al portal.");
        await refrescarClientesSinUsuario();
    } catch (e) {
        formErrors.value = e.response?.data?.errors ?? {};
        toast("danger", "No se pudo crear el usuario.");
    } finally {
        loading.value = false;
    }
}

function irAConsulta() {
    router.visit(route("consultas-medicas.index"));
}

async function volverAPaso2() {
    step.value = 2;
    await cargarMascotasCliente();
}

async function avanzarDesdePaso2SinCambios() {
    if (modoMascota.value === "existente" && mascotaSeleccionadaId.value) {
        mascotaId.value = mascotaSeleccionadaId.value;
        step.value = 3;
        return;
    }
    await guardarMascota();
}
</script>

<template>
    <AdminLayout title="Recepción">
        <div class="fixed top-4 right-4 z-50">
            <FwbToast v-if="showToast" :type="toastType" closable>{{ toastMsg }}</FwbToast>
        </div>

        <div class="recepcion-clinica w-full min-w-0 pb-6">
            <div class="mb-4 sm:mb-6">
                <h2 class="text-xl sm:text-2xl font-semibold vet-page-title">Recepción clínica</h2>
                
            </div>

            <div
                class="flex flex-wrap items-center gap-x-2 gap-y-1 mb-5 sm:mb-8 text-xs sm:text-sm"
                role="list"
                aria-label="Pasos del registro"
            >
                <span
                    role="listitem"
                    :class="step >= 1 ? 'font-bold text-emerald-600' : 'text-gray-400'"
                >1. Cliente</span>
                <span class="text-gray-300 hidden sm:inline" aria-hidden="true">→</span>
                <span
                    role="listitem"
                    :class="step >= 2 ? 'font-bold text-emerald-600' : 'text-gray-400'"
                >2. Mascota</span>
                <span class="text-gray-300 hidden sm:inline" aria-hidden="true">→</span>
                <span
                    role="listitem"
                    :class="step >= 3 ? 'font-bold text-emerald-600' : 'text-gray-400'"
                >3. Usuario</span>
                <span class="text-gray-300 hidden sm:inline" aria-hidden="true">→</span>
                <span
                    role="listitem"
                    :class="step >= 4 ? 'font-bold text-emerald-600' : 'text-gray-400'"
                >4. Listo</span>
            </div>

        <!-- Paso 1: Cliente -->
        <div v-if="step === 1" class="vet-panel p-4 sm:p-6 w-full">
            <h3 class="font-semibold mb-4">Datos del cliente (dueño)</h3>

            <div class="flex flex-wrap gap-4 mb-5 text-sm">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input v-model="modoCliente" type="radio" value="nuevo" class="rounded-full" />
                    Cliente nuevo
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input v-model="modoCliente" type="radio" value="existente" class="rounded-full" />
                    Cliente ya registrado
                </label>
            </div>

            <div v-if="modoCliente === 'existente'" class="mb-5 space-y-3 p-4 rounded-lg bg-gray-50 border">
                <div>
                    <InputLabel value="Buscar cliente" />
                    <TextInput
                        v-model="clienteBusqueda"
                        class="w-full mt-1"
                        placeholder="Nombre, apellido, CI o teléfono..."
                    />
                </div>
                <div>
                    <InputLabel value="Seleccionar cliente" />
                    <div class="relative mt-1">
                        <select
                            v-model="clienteSeleccionadoId"
                            class="w-full border rounded px-2 py-2 dark:bg-gray-700 dark:border-gray-600"
                            :disabled="isFetchingClientes"
                            @change="cargarClienteSeleccionado"
                        >
                            <option value="" disabled>
                                {{
                                    isFetchingClientes
                                        ? "Cargando..."
                                        : clientesOpciones.length
                                          ? "— Elija un cliente —"
                                          : "— Sin resultados —"
                                }}
                            </option>
                            <option v-for="c in clientesOpciones" :key="c.id" :value="String(c.id)">
                                {{ etiquetaCliente(c) }}
                            </option>
                        </select>
                        <FwbSpinner v-if="isFetchingClientes" size="4" class="absolute right-2 top-2" />
                    </div>
                    <p
                        v-if="!isFetchingClientes && !clientesOpciones.length"
                        class="mt-1 text-sm text-amber-700"
                    >
                        No hay clientes con ese criterio. Pruebe otro nombre, CI o teléfono.
                    </p>
                </div>
            </div>

            <p
                v-if="modoCliente === 'existente' && !clienteSeleccionadoId"
                class="mb-4 text-sm text-gray-500"
            >
                Busque y seleccione un cliente para ver o editar sus datos.
            </p>

            <div v-if="mostrarFormularioCliente" class="space-y-4">
                <h4 class="text-lg font-semibold text-gray-700">
                    {{ modoCliente === "nuevo" ? "Registrar cliente" : "Editar cliente" }}
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Nombre" />
                        <TextInput v-model="cliente.first_name" class="w-full mt-1" />
                        <InputError :message="formErrors.first_name?.[0]" />
                    </div>
                    <div>
                        <InputLabel value="Apellido" />
                        <TextInput v-model="cliente.last_name" class="w-full mt-1" />
                        <InputError :message="formErrors.last_name?.[0]" />
                    </div>
                    <div>
                        <InputLabel value="CI" />
                        <TextInput v-model="cliente.ci" class="w-full mt-1" />
                        <InputError :message="formErrors.ci?.[0]" />
                    </div>
                    <div>
                        <InputLabel value="Teléfono" />
                        <TextInput v-model="cliente.phone_number" class="w-full mt-1" />
                        <InputError :message="formErrors.phone_number?.[0]" />
                    </div>
                    <div>
                        <InputLabel value="Género" class="mb-2" />
                        <div class="flex flex-wrap gap-4">
                            <FwbRadio v-model="cliente.gender" value="0" label="Masculino" />
                            <FwbRadio v-model="cliente.gender" value="1" label="Femenino" />
                            <FwbRadio v-model="cliente.gender" value="2" label="Otro" />
                        </div>
                        <InputError :message="formErrors.gender?.[0]" />
                    </div>
                    <div>
                        <InputLabel value="Fecha de Nacimiento" />
                        <TextInput v-model="cliente.birthdate" type="date" class="w-full mt-1" />
                        <InputError :message="formErrors.birthdate?.[0]" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Dirección" />
                        <TextInput
                            v-model="cliente.address"
                            type="text"
                            class="w-full mt-1"
                            placeholder="Calle, zona, ciudad..."
                        />
                        <InputError :message="formErrors.address?.[0]" />
                    </div>
                    <div v-if="modoCliente === 'nuevo'" class="sm:col-span-2">
                        <InputLabel value="Correo (para usuario de acceso)" />
                        <TextInput v-model="cliente.email" type="email" class="w-full mt-1" />
                        <InputError :message="formErrors.email?.[0]" />
                    </div>
                    <div v-else class="sm:col-span-2">
                        <InputLabel value="Correo (para usuario de acceso)" />
                        <TextInput v-model="cliente.email" type="email" class="w-full mt-1" />
                        <p class="text-xs text-gray-500 mt-1">
                            Si ya tiene cuenta de portal, aquí verá su correo registrado.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <FwbButton
                    class="w-full sm:w-auto"
                    color="green"
                    :disabled="loading || (modoCliente === 'existente' && (!clienteSeleccionadoId || !clienteListo))"
                    @click="guardarCliente"
                >
                    {{
                        modoCliente === "existente"
                            ? (clienteDatosModificados() ? "Guardar cambios y continuar" : "Continuar a mascota")
                            : "Registrar y continuar"
                    }}
                </FwbButton>
            </div>
        </div>

        <!-- Paso 2: Mascota -->
        <div v-else-if="step === 2" class="vet-panel p-4 sm:p-6 w-full">
            <h3 class="font-semibold mb-4">Datos de la mascota</h3>

            <div class="flex flex-wrap gap-4 mb-5 text-sm">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input v-model="modoMascota" type="radio" value="nueva" class="rounded-full" />
                    Mascota nueva
                </label>
                <label
                    class="flex items-center gap-2 cursor-pointer"
                    :class="{ 'opacity-50': !hayMascotasRegistradas && modoCliente === 'nuevo' }"
                >
                    <input
                        v-model="modoMascota"
                        type="radio"
                        value="existente"
                        class="rounded-full"
                        :disabled="!hayMascotasRegistradas"
                    />
                    Mascota ya registrada
                </label>
            </div>

            <div
                v-if="modoMascota === 'existente'"
                class="mb-5 p-4 rounded-lg bg-gray-50 border"
            >
                <InputLabel value="Seleccionar mascota del cliente" />
                <select
                    v-model="mascotaSeleccionadaId"
                    class="mt-1 w-full border rounded px-2 py-2 dark:bg-gray-700 dark:border-gray-600"
                    @change="cargarMascotaSeleccionada"
                >
                    <option value="" disabled>— Elija una mascota —</option>
                    <option v-for="m in mascotasCliente" :key="m.id" :value="String(m.id)">
                        {{ m.name ?? m.nombre }}
                        <template v-if="m.breed?.name || m.raza?.nombre">
                            — {{ m.breed?.name ?? m.raza?.nombre }}
                        </template>
                    </option>
                </select>
                <p v-if="!hayMascotasRegistradas" class="text-xs text-amber-700 mt-2">
                    Este cliente aún no tiene mascotas. Registre una nueva.
                </p>
            </div>

            <div
                v-if="clienteId"
                class="mb-5 grid grid-cols-1 sm:grid-cols-2 gap-2 rounded-lg bg-gray-50 border p-3 text-sm"
            >
                <p><strong>Propietario:</strong> {{ cliente.first_name }} {{ cliente.last_name }}</p>
                <p><strong>CI:</strong> {{ cliente.ci }}</p>
            </div>

            <p
                v-if="modoMascota === 'existente' && !mascotaSeleccionadaId"
                class="mb-4 text-sm text-gray-500"
            >
                Seleccione una mascota del cliente o registre una nueva.
            </p>

            <div v-if="mostrarFormularioMascota" class="space-y-4">
                <FormSectionTitle
                    :title="modoMascota === 'nueva' ? 'Registrar mascota' : 'Editar mascota'"
                />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Nombre" />
                        <TextInput v-model="mascota.name" class="w-full mt-1" />
                        <InputError :message="formErrors.name?.[0]" />
                    </div>
                    <div>
                        <InputLabel value="Color" />
                        <TextInput v-model="mascota.color" class="w-full mt-1" />
                        <InputError :message="formErrors.color?.[0]" />
                    </div>
                    <div>
                        <InputLabel value="Especie" />
                        <div class="relative mt-1">
                            <select
                                v-model="specieSelect"
                                class="w-full border rounded px-2 py-2 dark:bg-gray-700 dark:border-gray-600"
                                @change="onSpecieSelectChange"
                            >
                                <option value="" disabled>Seleccione una especie</option>
                                <option v-for="s in allSpecies" :key="s.id" :value="String(s.id)">
                                    {{ s.name }}
                                </option>
                                <option value="otro">Otra...</option>
                            </select>
                        </div>
                        <TextInput
                            v-if="specieMode === 'otro'"
                            v-model="customSpecie"
                            type="text"
                            class="mt-2 w-full"
                            placeholder="Ej: Conejo, Reptil, Roedor..."
                        />
                    </div>

                    <div v-if="specieSelect && specieSelect !== 'otro'">
                        <InputLabel :value="`Raza (${selectedSpecieName})`" />
                        <div class="relative mt-1">
                            <select
                                v-model="breedSelect"
                                class="w-full border rounded px-2 py-2 dark:bg-gray-700 dark:border-gray-600"
                                :disabled="isFetchingBreeds"
                                @change="onBreedSelectChange"
                            >
                                <option value="" disabled>
                                    {{ isFetchingBreeds ? "Cargando razas..." : "Seleccione una raza" }}
                                </option>
                                <option v-for="b in breedsForSpecie" :key="b.id" :value="String(b.id)">
                                    {{ b.name }}
                                </option>
                                <option value="otro">Otra...</option>
                            </select>
                            <FwbSpinner v-if="isFetchingBreeds" size="4" class="absolute right-2 top-2" />
                        </div>
                        <TextInput
                            v-if="breedMode === 'otro'"
                            v-model="customBreed"
                            type="text"
                            class="mt-2 w-full"
                            placeholder="Ej: Labrador, Caniche, Mestizo..."
                        />
                        <InputError :message="formErrors.breed_id?.[0]" />
                    </div>

                    <div v-else-if="specieSelect === 'otro'">
                        <InputLabel value="Raza (otra especie)" />
                        <TextInput
                            v-model="customBreed"
                            type="text"
                            class="mt-1 w-full"
                            placeholder="Ej: Angora, Mestizo..."
                        />
                        <InputError :message="formErrors.breed_id?.[0]" />
                    </div>

                    <div>
                        <InputLabel value="Edad" />
                        <TextInput v-model="mascota.age" type="text" class="w-full mt-1" />
                        <InputError :message="formErrors.age?.[0]" />
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel value="Foto de la mascota (opcional)" />
                        <div class="mt-2 flex items-start gap-4">
                            <div v-if="photoPreview" class="shrink-0">
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
                                <InputError :message="formErrors.photo?.[0]" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex flex-col sm:flex-row sm:justify-between gap-3">
                <FwbButton class="w-full sm:w-auto" color="alternative" @click="step = 1">Atrás</FwbButton>
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <FwbButton
                        v-if="modoMascota === 'existente' && mascotaSeleccionadaId"
                        class="w-full sm:w-auto"
                        color="alternative"
                        :disabled="loading"
                        @click="avanzarDesdePaso2SinCambios"
                    >
                        Continuar sin cambios
                    </FwbButton>
                    <FwbButton
                        class="w-full sm:w-auto"
                        color="green"
                        :disabled="loading || (modoMascota === 'nueva' && !mostrarFormularioMascota) || (modoMascota === 'existente' && !mascotaSeleccionadaId)"
                        @click="guardarMascota"
                    >
                        {{ modoMascota === "existente" ? "Guardar y continuar" : "Registrar y continuar" }}
                    </FwbButton>
                </div>
            </div>
        </div>

        <!-- Paso 3: Usuario portal -->
        <div v-else-if="step === 3" class="vet-panel p-4 sm:p-6 w-full max-w-3xl">
            <h3 class="font-semibold mb-4">Cuenta de acceso al portal</h3>
            <p class="text-sm text-gray-500 mb-4">
                El usuario creado tendrá rol <strong>cliente</strong> y acceso a «Mi cuenta» / carnet virtual.
            </p>
            <div
                v-if="clienteTieneUsuario"
                class="mb-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-sm text-emerald-800"
            >
                El cliente seleccionado ya tiene usuario de acceso al carnet virtual.
            </div>

            <template v-else>
                <label class="flex items-center gap-2 mb-4">
                    <input v-model="usuario.crear_cuenta" type="checkbox" class="rounded" />
                    Crear usuario para que el dueño vea el carnet virtual
                </label>

                <div v-if="usuario.crear_cuenta" class="space-y-4">
                    <div>
                        <InputLabel value="Cliente sin usuario registrado" />
                        <p class="text-xs text-gray-500 mb-1">
                            Elija el dueño que aún no tiene acceso al portal.
                        </p>
                        <select
                            v-model="clienteUsuarioId"
                            class="w-full border rounded px-2 py-2 dark:bg-gray-700 dark:border-gray-600"
                        >
                            <option value="" disabled>— Seleccione un cliente —</option>
                            <option
                                v-for="c in clientesSinUsuarioLista"
                                :key="c.id"
                                :value="String(c.id)"
                            >
                                {{ c.nombre }} {{ c.apellido }} — CI {{ c.ci }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Correo de acceso" />
                        <TextInput v-model="usuario.email" type="email" class="w-full mt-1" />
                        <InputError :message="formErrors.email?.[0]" />
                        <p class="text-xs text-gray-500 mt-2">La contraseña se generará automáticamente.</p>
                    </div>
                </div>
            </template>

            <div class="mt-6 flex flex-col sm:flex-row sm:justify-between gap-3">
                <FwbButton class="w-full sm:w-auto" color="alternative" @click="volverAPaso2">Atrás</FwbButton>
                <FwbButton class="w-full sm:w-auto" color="green" :disabled="loading" @click="guardarUsuario">
                    {{ clienteTieneUsuario || !usuario.crear_cuenta ? "Finalizar" : "Crear acceso y finalizar" }}
                </FwbButton>
            </div>
        </div>

        <!-- Paso 4: Listo -->
        <div v-else class="vet-panel p-6 sm:p-8 text-center w-full max-w-xl mx-auto">
            <i class="fa-solid fa-circle-check text-5xl text-emerald-500 mb-4"></i>
            <h3 class="text-xl font-semibold mb-2">Recepción completada</h3>
            <p class="text-gray-600 mb-6">
                Cliente #{{ clienteId }}, mascota #{{ mascotaId }}.
                <span v-if="usuario.crear_cuenta && !clienteTieneUsuario">
                    El dueño puede ingresar con su correo cuando se cree el acceso.
                </span>
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <FwbButton color="green" @click="irAConsulta">Abrir consultas médicas</FwbButton>
                <FwbButton tag="a" :href="route('recepcion.index')" color="alternative">Nueva recepción</FwbButton>
            </div>
        </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.recepcion-clinica :deep(select),
.recepcion-clinica :deep(input[type="date"]),
.recepcion-clinica :deep(input[type="file"]) {
    max-width: 100%;
}

.recepcion-clinica :deep(.flex.flex-wrap.gap-4) {
    gap: 0.75rem 1rem;
}
</style>
