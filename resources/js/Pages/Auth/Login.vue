<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import InputError from '@/Components/InputError.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { FwbButton, FwbCheckbox, FwbInput } from 'flowbite-vue'
import { ref } from 'vue'

const showPassword = ref(false)
const isLoading = ref(false)

defineProps({
  canResetPassword: Boolean,
  status: String,
})

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const submit = () => {
  isLoading.value = true
  form.transform((data) => ({
    ...data,
    remember: form.remember ? 'on' : '',
  })).post(route('login'), {
    onFinish: () => {
      isLoading.value = false
      form.reset('password')
    },
  })
}
</script>

<template>
  <AuthLayout title="Iniciar sesión" subtitle="Accede al panel de gestión veterinaria">
    <div v-if="status" class="mb-4 p-3 rounded-lg bg-emerald-50 text-emerald-800 text-sm text-center border border-emerald-200">
      {{ status }}
    </div>

    <form @submit.prevent="submit" class="space-y-5">
      <div>
        <label for="email" class="block text-sm font-medium text-emerald-800 mb-1.5">Correo electrónico</label>
        <FwbInput
          id="email"
          v-model="form.email"
          type="email"
          required
          placeholder="tu@ejemplo.com"
          autocomplete="username"
        >
          <template #suffix>
            <i class="fa-solid fa-envelope text-emerald-400"></i>
          </template>
        </FwbInput>
        <InputError class="mt-2" :message="form.errors.email" />
      </div>

      <div>
        <label for="password" class="block text-sm font-medium text-emerald-800 mb-1.5">Contraseña</label>
        <FwbInput
          id="password"
          v-model="form.password"
          :type="showPassword ? 'text' : 'password'"
          required
          placeholder="••••••••"
        >
          <template #suffix>
            <button type="button" @click="togglePassword" class="text-emerald-500 hover:text-emerald-700 focus:outline-none">
              <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
            </button>
          </template>
        </FwbInput>
        <InputError class="mt-2" :message="form.errors.password" />
      </div>

      <div class="flex items-center justify-between">
        <FwbCheckbox v-model="form.remember" label="Recordarme" />
        <Link
          v-if="canResetPassword"
          :href="route('password.request')"
          class="text-sm font-medium text-emerald-600 hover:text-emerald-800"
        >
          ¿Olvidaste tu contraseña?
        </Link>
      </div>

      <FwbButton type="submit" color="green" :disabled="isLoading" :loading="isLoading" class="w-full">
        <i class="fa-solid fa-right-to-bracket mr-2"></i>
        Iniciar sesión
      </FwbButton>
    </form>
  </AuthLayout>
</template>
