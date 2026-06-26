<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import InputError from '@/Components/InputError.vue'
import { useForm } from '@inertiajs/vue3'
import { FwbButton, FwbInput } from 'flowbite-vue'
import { ref } from 'vue'

const form = useForm({ password: '' })
const passwordInput = ref(null)

const submit = () => {
  form.post(route('password.confirm'), {
    onFinish: () => {
      form.reset()
      passwordInput.value?.focus()
    },
  })
}
</script>

<template>
  <AuthLayout title="Área segura" subtitle="Confirma tu contraseña para continuar">
    <p class="text-sm text-emerald-700/80 mb-4">
      Esta sección requiere verificar tu identidad antes de continuar.
    </p>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label for="password" class="block text-sm font-medium text-emerald-800 mb-1.5">Contraseña</label>
        <FwbInput
          id="password"
          ref="passwordInput"
          v-model="form.password"
          type="password"
          required
          autofocus
          autocomplete="current-password"
        />
        <InputError class="mt-2" :message="form.errors.password" />
      </div>

      <FwbButton type="submit" color="green" class="w-full" :disabled="form.processing">
        Confirmar
      </FwbButton>
    </form>
  </AuthLayout>
</template>
