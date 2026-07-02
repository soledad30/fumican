<template>
  <div class="welcome-page min-h-screen flex flex-col text-emerald-950">
  <Head title="Inicio" />

    <!-- Header -->
    <header class="welcome-header sticky top-0 z-40 border-b border-emerald-800/20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-3">
          <div class="welcome-logo">
            <i class="fa-solid fa-paw text-lg"></i>
          </div>
          <div>
            <p class="font-bold text-lg leading-tight text-white">Fumican Vet</p>
            <p class="text-xs text-emerald-200/80 hidden sm:block">Clínica veterinaria integral</p>
          </div>
        </div>
        <a
          :href="route('login')"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-white text-sm font-semibold border border-white/20 transition"
        >
          <i class="fa-solid fa-right-to-bracket"></i>
          Iniciar sesión
        </a>
      </div>
    </header>

    <!-- Hero -->
    <section class="welcome-hero relative overflow-hidden">
      <div class="hero-pattern absolute inset-0 opacity-30"></div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 py-16 lg:py-24 relative z-10">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
          <div class="text-white">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 text-emerald-100 text-sm mb-6 border border-white/20">
              <i class="fa-solid fa-heart-pulse"></i>
              Atención profesional en Santa Cruz
            </span>
            <h1 class="text-4xl sm:text-5xl font-bold leading-tight mb-5">
              Cuidamos a tu mascota con <span class="text-emerald-200">amor y ciencia</span>
            </h1>
            <p class="text-lg text-emerald-100/90 mb-8 max-w-xl">
              Consultas, vacunas, cirugías menores y más. Reserva en línea y recibe atención personalizada de nuestro equipo veterinario.
            </p>

            <div class="flex flex-col sm:flex-row gap-3 mb-10">
              <div class="relative flex-1 max-w-md">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600/70"></i>
                <input
                  v-model="serviceSearch"
                  type="search"
                  placeholder="Buscar servicio: consulta, vacuna, baño..."
                  class="welcome-input pl-11"
                />
              </div>
              <button
                type="button"
                @click="scrollToReserva"
                class="welcome-btn-primary shrink-0"
              >
                <i class="fa-solid fa-calendar-check mr-2"></i>
                {{ showForm ? 'Ver formulario' : 'Solicitar cita' }}
              </button>
            </div>

            <div class="grid grid-cols-3 gap-4 max-w-lg">
              <div class="hero-stat">
                <p class="text-2xl font-bold text-white">24h</p>
                <p class="text-xs text-emerald-200/80">Urgencias</p>
              </div>
              <div class="hero-stat">
                <p class="text-2xl font-bold text-white">{{ servicios.length }}+</p>
                <p class="text-xs text-emerald-200/80">Servicios</p>
              </div>
              <div class="hero-stat">
                <p class="text-2xl font-bold text-white">100%</p>
                <p class="text-xs text-emerald-200/80">Compromiso</p>
              </div>
            </div>
          </div>

          <div class="hidden lg:block">
            <div class="hero-card float-animation">
              <div class="hero-card-inner">
                <div class="grid grid-cols-2 gap-4">
                  <div class="hero-feature">
                    <i class="fa-solid fa-stethoscope text-2xl text-emerald-600"></i>
                    <p class="font-semibold mt-2">Consulta médica</p>
                    <p class="text-sm text-emerald-800/70">Diagnóstico y tratamiento</p>
                  </div>
                  <div class="hero-feature">
                    <i class="fa-solid fa-syringe text-2xl text-teal-600"></i>
                    <p class="font-semibold mt-2">Vacunación</p>
                    <p class="text-sm text-emerald-800/70">Plan preventivo</p>
                  </div>
                  <div class="hero-feature">
                    <i class="fa-solid fa-scissors text-2xl text-emerald-600"></i>
                    <p class="font-semibold mt-2">Peluquería</p>
                    <p class="text-sm text-emerald-800/70">Higiene y estética</p>
                  </div>
                  <div class="hero-feature">
                    <i class="fa-solid fa-pills text-2xl text-teal-600"></i>
                    <p class="font-semibold mt-2">Farmacia</p>
                    <p class="text-sm text-emerald-800/70">Medicamentos certificados</p>
                  </div>
                </div>
                <div class="mt-6 p-4 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-center">
                  <p class="text-sm opacity-90">Desde</p>
                  <p class="text-3xl font-bold">Bs. {{ precioDesde }}</p>
                  <p class="text-xs opacity-80 mt-1">según servicio seleccionado</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="hero-wave">
        <svg viewBox="0 0 1440 80" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" class="w-full h-16 sm:h-20">
          <path d="M0 40C240 80 480 0 720 40C960 80 1200 0 1440 40V80H0V40Z" fill="#ecfdf5"/>
        </svg>
      </div>
    </section>

    <!-- Servicios destacados -->
    <section class="bg-emerald-50 py-16 px-4 sm:px-6">
      <div class="max-w-7xl mx-auto">
        <div class="text-center mb-10">
          <h2 class="text-3xl font-bold text-emerald-900 mb-2">Nuestros servicios</h2>
          <p class="text-emerald-700/80 max-w-2xl mx-auto">
            Elige el servicio que necesitas y agenda tu cita en pocos pasos.
          </p>
        </div>

        <div v-if="filteredServicios.length" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
          <article
            v-for="s in filteredServicios"
            :key="s.id"
            class="service-card group"
            @click="selectServicio(s)"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="service-icon">
                <i :class="iconoServicio(s.nombre)"></i>
              </div>
              <span class="text-lg font-bold text-emerald-700">Bs. {{ Number(s.precio).toFixed(0) }}</span>
            </div>
            <h3 class="font-bold text-emerald-900 mt-4 group-hover:text-emerald-700 transition">{{ s.nombre }}</h3>
            <p class="text-sm text-emerald-800/70 mt-2 line-clamp-2">
              {{ s.descripcion || 'Atención veterinaria profesional para el bienestar de tu mascota.' }}
            </p>
            <p class="mt-4 text-sm font-semibold text-emerald-600 flex items-center gap-1">
              Reservar ahora <i class="fa-solid fa-arrow-right text-xs group-hover:translate-x-1 transition-transform"></i>
            </p>
          </article>
        </div>
        <p v-else class="text-center text-emerald-700/60 py-8">
          No hay servicios que coincidan con «{{ serviceSearch }}».
        </p>
      </div>
    </section>

    <!-- Reserva -->
    <section id="reserva" class="py-16 px-4 sm:px-6 bg-white">
      <div class="max-w-3xl mx-auto">
        <div class="text-center mb-8">
          <h2 class="text-3xl font-bold text-emerald-900 mb-2">Reserva tu cita</h2>
          <p class="text-emerald-700/80">Completa el formulario y confirma con pago QR</p>
        </div>

        <div class="reserva-card">
          <form class="space-y-5" @submit.prevent>
            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <label class="form-label">Nombre completo</label>
                <input v-model="appointmentForm.name" type="text" placeholder="Tu nombre" required class="welcome-input-light" />
              </div>
              <div>
                <label class="form-label">Teléfono</label>
                <input v-model="appointmentForm.phone" type="tel" placeholder="70000000" required class="welcome-input-light" />
              </div>
              <div>
                <label class="form-label">Correo electrónico</label>
                <input v-model="appointmentForm.email" type="email" placeholder="correo@ejemplo.com" required class="welcome-input-light" />
              </div>
              <div>
                <label class="form-label">Nombre de la mascota</label>
                <input v-model="appointmentForm.petName" type="text" placeholder="Firulais" required class="welcome-input-light" />
              </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <label class="form-label">Servicio</label>
                <select v-model="appointmentForm.serviceId" required class="welcome-input-light" @change="onServiceChange">
                  <option value="">Selecciona un servicio</option>
                  <option v-for="s in filteredServicios" :key="s.id" :value="s.id">
                    {{ s.nombre }} — Bs. {{ Number(s.precio).toFixed(2) }}
                  </option>
                </select>
              </div>
              <div>
                <label class="form-label">Fecha</label>
                <input v-model="appointmentForm.date" type="date" required class="welcome-input-light" :min="fechaMinima" />
              </div>
            </div>
            <p v-if="selectedServicio?.descripcion" class="text-sm text-emerald-700/80 bg-emerald-50 rounded-lg px-4 py-2">
              <i class="fa-solid fa-circle-info mr-1"></i> {{ selectedServicio.descripcion }}
            </p>

            <div>
              <label class="form-label">Horario disponible</label>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="time in availableTimeSlots"
                  :key="time"
                  type="button"
                  @click="appointmentForm.timeSlot = time"
                  :class="[
                    'slot-btn',
                    appointmentForm.timeSlot === time ? 'slot-btn--active' : '',
                  ]"
                >
                  {{ time }}
                </button>
              </div>
            </div>

            <div>
              <label class="form-label">Comentarios (opcional)</label>
              <textarea
                v-model="appointmentForm.comment"
                rows="3"
                placeholder="Síntomas, alergias o notas para el veterinario..."
                class="welcome-input-light resize-none"
              ></textarea>
            </div>

            <button type="button" @click="openPaymentModal" class="welcome-btn-primary w-full justify-center text-base py-3">
              <i class="fa-solid fa-qrcode mr-2"></i>
              Pagar anticipo ({{ porcentajeAnticipo }}%) y confirmar reserva
            </button>
            <p v-if="selectedServicio" class="text-sm text-emerald-700/80 mt-2 text-center">
              Anticipo: <strong>Bs. {{ montoAnticipoMostrar }}</strong>
              de Bs. {{ Number(selectedServicio.precio).toFixed(2) }} —
              el saldo se paga al atender en la clínica.
            </p>
          </form>
        </div>
      </div>
    </section>

    <!-- Por qué elegirnos -->
    <section class="py-16 px-4 sm:px-6 bg-gradient-to-b from-emerald-50 to-white">
      <div class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-12 items-center">
        <div>
          <h2 class="text-3xl font-bold text-emerald-900 mb-4">Amor, cuidado y compromiso</h2>
          <p class="text-emerald-800/80 text-lg leading-relaxed mb-6">
            En Fumican Vet ofrecemos atención médica de calidad con un equipo apasionado por el bienestar animal,
            tecnología moderna y un ambiente cálido para tu mascota y tu familia.
          </p>
          <ul class="space-y-4">
            <li v-for="item in ventajas" :key="item.title" class="flex gap-4">
              <div class="ventaja-icon shrink-0">
                <i :class="item.icon"></i>
              </div>
              <div>
                <p class="font-semibold text-emerald-900">{{ item.title }}</p>
                <p class="text-sm text-emerald-800/70">{{ item.text }}</p>
              </div>
            </li>
          </ul>
        </div>
        <div class="about-visual">
          <div class="about-visual-card">
            <i class="fa-solid fa-dog text-6xl text-emerald-600/30 absolute top-8 left-8"></i>
            <i class="fa-solid fa-cat text-5xl text-teal-600/30 absolute bottom-12 right-10"></i>
            <div class="relative z-10 text-center p-8">
              <div class="w-20 h-20 mx-auto rounded-2xl bg-white shadow-lg flex items-center justify-center mb-4">
                <i class="fa-solid fa-house-medical text-3xl text-emerald-600"></i>
              </div>
              <p class="text-xl font-bold text-emerald-900">Tu veterinaria de confianza</p>
              <p class="text-emerald-700/80 mt-2 text-sm">Mascotas felices, dueños tranquilos</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Modal pago QR -->
    <div v-if="showPaymentModal" class="fixed inset-0 bg-emerald-950/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl p-6 w-full max-w-md relative shadow-2xl">
        <button
          type="button"
          @click="closePaymentModal"
          class="absolute top-4 right-4 w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600"
        >&times;</button>

        <h3 class="text-xl font-bold text-emerald-900 mb-1">Pago anticipo con QR</h3>
        <p class="text-sm text-emerald-700/70 mb-1">Escanea con tu app bancaria</p>
        <p v-if="montoAnticipoPago > 0" class="text-sm font-semibold text-emerald-800 mb-4">
          Monto anticipo ({{ porcentajeAnticipo }}%): Bs. {{ montoAnticipoPago.toFixed(2) }}
        </p>
        <p v-else class="text-sm text-emerald-700/70 mb-4">Escanea con tu app bancaria</p>

        <div class="flex justify-center mb-4 min-h-[220px] items-center bg-emerald-50 rounded-xl p-4">
          <img v-if="qrImageUrl" :src="qrImageUrl" alt="Código QR de pago" class="max-w-full max-h-[280px] rounded-lg" />
          <div v-else class="flex flex-col items-center text-emerald-700/60 text-sm">
            <svg class="animate-spin h-8 w-8 text-emerald-500 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
            </svg>
            Generando código QR...
          </div>
        </div>

        <div v-if="paymentStatus === 'verificando'" class="text-center text-sm text-emerald-700">
          <span class="inline-flex items-center">
            <svg class="animate-spin mr-2 h-5 w-5 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
            </svg>
            Verificando pago...
          </span>
        </div>
        <div v-else-if="paymentStatus === 'completado'" class="text-center text-emerald-600 font-semibold py-2">
          <i class="fa-solid fa-circle-check mr-1"></i> Pago verificado correctamente
        </div>
        <button
          v-else-if="qrImageUrl"
          type="button"
          @click="confirmPayment"
          class="welcome-btn-primary w-full justify-center mt-2"
        >
          Confirmar pago manualmente
        </button>
      </div>
    </div>

    <FwbToast v-if="showToast" :type="toastType" class="fixed top-5 right-5 z-50">
      {{ toastMsg }}
    </FwbToast>

    <div v-if="loadingPdf" class="fixed inset-0 bg-emerald-950/60 backdrop-blur-sm flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-2xl flex items-center gap-3 shadow-xl">
        <svg class="animate-spin h-6 w-6 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
        </svg>
        <span class="text-emerald-900 font-medium">Generando comprobante PDF...</span>
      </div>
    </div>

    <!-- Footer -->
    <footer class="welcome-footer mt-auto">
      <div class="max-w-7xl mx-auto px-6 py-12 grid grid-cols-1 md:grid-cols-3 gap-8">
        <div>
          <div class="flex items-center gap-2 mb-3">
            <div class="welcome-logo w-9 h-9 text-sm">
              <i class="fa-solid fa-paw"></i>
            </div>
            <span class="font-bold text-white">Fumican Vet</span>
          </div>
          <p class="text-sm text-emerald-200/80">Cuidamos a tu mascota como si fuera parte de nuestra familia.</p>
        </div>
        <div>
          <h4 class="font-bold text-white mb-3">Contacto</h4>
          <p class="text-sm text-emerald-200/80 flex items-center gap-2 mb-1"><i class="fa-solid fa-phone w-4"></i> (591) 700-00000</p>
          <p class="text-sm text-emerald-200/80 flex items-center gap-2 mb-1"><i class="fa-solid fa-location-dot w-4"></i> Cambódromo, 5to anillo, Santa Cruz</p>
          <p class="text-sm text-emerald-200/80 flex items-center gap-2"><i class="fa-solid fa-envelope w-4"></i> contacto@fumican.com</p>
        </div>
        <div>
          <h4 class="font-bold text-white mb-3">Horario</h4>
          <p class="text-sm text-emerald-200/80">Lun – Vie: 9:00 – 18:00</p>
          <p class="text-sm text-emerald-200/80">Sábado: 9:00 – 13:00</p>
          <p class="text-sm text-emerald-200/80">Domingo: Cerrado</p>
        </div>
      </div>
      <div class="text-center text-sm text-emerald-300/60 py-4 border-t border-emerald-800/50">
        © {{ new Date().getFullYear() }} Veterinaria Fumican. Todos los derechos reservados.
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import { FwbToast } from 'flowbite-vue'
import { esPagoQrConfirmado } from '@/Composables/usePagoQr'

const props = defineProps({
  servicios: { type: Array, default: () => [] },
})

const showForm = ref(true)
const showPaymentModal = ref(false)
const paymentStatus = ref('')
const loadingPdf = ref(false)
const serviceSearch = ref('')

const showToast = ref(false)
const toastMsg = ref('')
const toastType = ref('success')

const qrImageUrl = ref('')
const transactionId = ref('')
const numeroPago = ref('')
const montoAnticipoPago = ref(0)
const porcentajeAnticipo = ref(20)

const montoAnticipoMostrar = computed(() => {
  const precio = Number(selectedServicio.value?.precio) || 0
  if (!precio) return '0.00'
  const monto = Math.max(0.01, Math.round(precio * porcentajeAnticipo.value) / 100)
  return monto.toFixed(2)
})

const appointmentForm = ref({
  name: '',
  phone: '',
  email: '',
  petName: '',
  serviceId: '',
  service: '',
  date: '',
  timeSlot: '',
  comment: '',
})

const ventajas = [
  { icon: 'fa-solid fa-user-doctor', title: 'Veterinarios certificados', text: 'Equipo con experiencia en clínica y cirugía menor.' },
  { icon: 'fa-solid fa-microscope', title: 'Tecnología moderna', text: 'Diagnóstico preciso y seguimiento del historial clínico.' },
  { icon: 'fa-solid fa-shield-heart', title: 'Trato humano', text: 'Ambiente tranquilo para reducir el estrés de tu mascota.' },
]

const fechaMinima = computed(() => new Date().toISOString().split('T')[0])

const filteredServicios = computed(() => {
  const term = serviceSearch.value.trim().toLowerCase()
  if (!term) return props.servicios
  return props.servicios.filter((s) =>
    s.nombre?.toLowerCase().includes(term) ||
    s.descripcion?.toLowerCase().includes(term)
  )
})

const selectedServicio = computed(() =>
  props.servicios.find((s) => String(s.id) === String(appointmentForm.value.serviceId)) ?? null
)

const precioDesde = computed(() => {
  if (!props.servicios.length) return '0'
  const min = Math.min(...props.servicios.map((s) => Number(s.precio) || 0))
  return min.toFixed(0)
})

function iconoServicio(nombre) {
  const n = (nombre || '').toLowerCase()
  if (n.includes('vacun')) return 'fa-solid fa-syringe'
  if (n.includes('baño') || n.includes('pelu')) return 'fa-solid fa-scissors'
  if (n.includes('cirug')) return 'fa-solid fa-kit-medical'
  if (n.includes('consult')) return 'fa-solid fa-stethoscope'
  return 'fa-solid fa-paw'
}

function scrollToReserva() {
  showForm.value = true
  document.getElementById('reserva')?.scrollIntoView({ behavior: 'smooth' })
}

function onServiceChange() {
  appointmentForm.value.service = selectedServicio.value?.nombre ?? ''
}

function selectServicio(s) {
  appointmentForm.value.serviceId = s.id
  appointmentForm.value.service = s.nombre
  showForm.value = true
  scrollToReserva()
}

function showToastMessage(message, type = 'success') {
  toastMsg.value = message
  toastType.value = type
  showToast.value = true
  setTimeout(() => { showToast.value = false }, 3000)
}

const availableTimeSlots = ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00']

async function openPaymentModal() {
  const data = appointmentForm.value

  if (
    data.name && data.phone && data.email && data.petName &&
    data.serviceId && data.date && data.timeSlot
  ) {
    qrImageUrl.value = ''
    showPaymentModal.value = true

    const qr = await generateQrCode()

    if (qr) {
      qrImageUrl.value = 'data:image/png;base64,' + qr
      confirmPayment()
    } else {
      closePaymentModal()
    }
  } else {
    showToastMessage('Completa todos los campos obligatorios antes de pagar.', 'danger')
    scrollToReserva()
  }
}

async function confirmPayment() {
  paymentStatus.value = 'verificando'

  let attempts = 0
  const maxAttempts = 6

  const interval = setInterval(async () => {
    attempts++

    const confirmado = await verifyPurchase()

    if (confirmado) {
      clearInterval(interval)
      paymentStatus.value = 'completado'
      loadingPdf.value = true

      setTimeout(async () => {
        await downloadReservationPdf()
        loadingPdf.value = false
        closePaymentModal()

        showToastMessage('Reserva realizada con éxito', 'success')

        appointmentForm.value = {
          name: '', phone: '', email: '', petName: '',
          serviceId: '', service: '', date: '', timeSlot: '', comment: '',
        }
      }, 3000)
    }

    if (attempts >= maxAttempts) {
      clearInterval(interval)
      paymentStatus.value = ''
      showToastMessage('El pago no fue confirmado a tiempo. Intenta nuevamente.', 'danger')
    }
  }, 5000)
}

function closePaymentModal() {
  showPaymentModal.value = false
  paymentStatus.value = ''
}

async function generateQrCode() {
  try {
    const monto = Number(montoAnticipoMostrar.value)
    const response = await fetch('/api/generar-qr', {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
      },
      body: JSON.stringify({
        name: appointmentForm.value.name,
        phone: appointmentForm.value.phone,
        email: appointmentForm.value.email,
        serviceId: appointmentForm.value.serviceId,
        descripcion: appointmentForm.value.service || 'Reserva cita veterinaria',
        monto,
      }),
    })

    if (!response.ok) throw new Error('Error al generar el QR')

    const data = await response.json()
    if (!data.success) {
      throw new Error(data.message || 'Error al generar el QR')
    }
    transactionId.value = data.numeroTransaccion || ''
    numeroPago.value = data.numeroPago || ''
    montoAnticipoPago.value = Number(data.montoAnticipo) || 0
    if (data.porcentajeAnticipo) porcentajeAnticipo.value = data.porcentajeAnticipo
    return data.qrImage
  } catch (error) {
    console.error('Error al generar el QR:', error)
    showToastMessage('Ocurrió un error al generar el QR', 'danger')
  }
}

async function verifyPurchase() {
  try {
    const response = await fetch('/api/verificar-pago', {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
      },
      body: JSON.stringify({
        numeroTransaccion: transactionId.value || undefined,
        numeroPago: numeroPago.value || undefined,
      }),
    })

    const result = await response.json()
    return esPagoQrConfirmado(result)
  } catch (error) {
    console.error('Error al verificar el pago:', error)
    return false
  }
}

async function downloadReservationPdf() {
  try {
    const response = await fetch('/reservas/pdf', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
      },
      body: JSON.stringify({
        ...appointmentForm.value,
        numeroTransaccion: transactionId.value,
        montoAnticipo: montoAnticipoPago.value || montoAnticipoMostrar.value,
      }),
    })

    if (!response.ok) {
      const err = await response.json().catch(() => ({}))
      throw new Error(err.message || 'No se pudo generar el comprobante')
    }

    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'reserva-fumican.pdf'
    document.body.appendChild(a)
    a.click()
    a.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Error al descargar el PDF:', error)
    showToastMessage('Ocurrió un error al generar el PDF', 'danger')
  }
}
</script>

<style scoped>
.welcome-page {
  background: #ecfdf5;
}

.welcome-header {
  background: linear-gradient(90deg, #064e3b 0%, #047857 100%);
  backdrop-filter: blur(8px);
}

.welcome-logo {
  @apply w-10 h-10 rounded-xl bg-white/15 border border-white/25 flex items-center justify-center text-white;
}

.welcome-hero {
  background: linear-gradient(135deg, #064e3b 0%, #047857 40%, #0d9488 100%);
}

.hero-pattern {
  background-image: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.08) 0%, transparent 50%),
    radial-gradient(circle at 80% 20%, rgba(255,255,255,0.06) 0%, transparent 40%);
}

.hero-stat {
  @apply rounded-xl bg-white/10 border border-white/15 px-3 py-3 text-center backdrop-blur-sm;
}

.hero-card {
  @apply rounded-2xl p-1 bg-white/20 backdrop-blur-md shadow-2xl;
}

.hero-card-inner {
  @apply rounded-xl bg-white/95 p-6 text-emerald-900;
}

.hero-feature {
  @apply p-4 rounded-xl bg-emerald-50 border border-emerald-100;
}

.hero-wave {
  @apply absolute bottom-0 left-0 right-0 leading-none;
}

.float-animation {
  animation: float 6s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}

.welcome-input {
  @apply w-full px-4 py-3 rounded-xl border-0 bg-white text-emerald-900 placeholder-emerald-600/50 shadow-lg focus:outline-none focus:ring-2 focus:ring-emerald-300;
}

.welcome-input-light {
  @apply w-full px-4 py-2.5 rounded-lg border border-emerald-200 bg-white text-emerald-900 placeholder-emerald-600/40 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 transition;
}

.welcome-btn-primary {
  @apply inline-flex items-center px-6 py-3 rounded-xl bg-white text-emerald-800 font-bold shadow-lg hover:bg-emerald-50 hover:shadow-xl transition active:scale-[0.98];
}

.service-card {
  @apply p-5 rounded-2xl bg-white border border-emerald-100 shadow-sm hover:shadow-lg hover:border-emerald-300 cursor-pointer transition-all duration-200;
}

.service-icon {
  @apply w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-100 to-teal-100 flex items-center justify-center text-emerald-700 text-xl;
}

.reserva-card {
  @apply bg-white rounded-2xl border border-emerald-100 shadow-xl p-6 sm:p-8;
}

.form-label {
  @apply block text-sm font-medium text-emerald-800 mb-1.5;
}

.slot-btn {
  @apply px-4 py-2 rounded-lg border border-emerald-200 bg-white text-emerald-800 text-sm font-medium hover:bg-emerald-50 transition;
}

.slot-btn--active {
  @apply bg-emerald-600 text-white border-emerald-700 shadow-md;
}

.ventaja-icon {
  @apply w-11 h-11 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center;
}

.about-visual-card {
  @apply relative rounded-3xl bg-gradient-to-br from-emerald-100 via-teal-50 to-emerald-50 border border-emerald-200 min-h-[320px] flex items-center justify-center overflow-hidden shadow-inner;
}

.welcome-footer {
  background: linear-gradient(180deg, #064e3b 0%, #022c22 100%);
}
</style>
