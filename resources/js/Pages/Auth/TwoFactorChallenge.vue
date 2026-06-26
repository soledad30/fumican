<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import InputError from '@/Components/InputError.vue'
import { useForm } from '@inertiajs/vue3'
import { FwbButton, FwbInput } from 'flowbite-vue'
import { nextTick, ref } from 'vue'

const recovery = ref(false)
const form = useForm({ code: '', recovery_code: '' })
const recoveryCodeInput = ref(null)
const codeInput = ref(null)

const toggleRecovery = async () => {
  recovery.value ^= true
  await nextTick()
  if (recovery.value) {
    recoveryCodeInput.value?.focus()
    form.code = ''
  } else {
    codeInput.value?.focus()
    form.recovery_code = ''
  }
}

const submit = () => form.post(route('two-factor.login'))
</script>

<template>
  <AuthLayout
    :title="recovery ? 'Código de recuperación' : 'Autenticación en dos pasos'"
    subtitle="Confirma tu identidad para continuar"
  >
    <p class="text-sm text-emerald-700/80 mb-4">
      <template v-if="!recovery">
        Ingresa el código de tu aplicación de autenticación.
      </template>
      <template v-else>
        Ingresa uno de tus códigos de recuperación de emergencia.
      </template>
    </p>

    <form @submit.prevent="submit" class="space-y-4">
      <div v-if="!recovery">
        <label for="code" class="block text-sm font-medium text-emerald-800 mb-1.5">Código</label>
        <FwbInput
          id="code"
          ref="codeInput"
          v-model="form.code"
          type="text"
          inputmode="numeric"
          autofocus
          autocomplete="one-time-code"
        />
        <InputError class="mt-2" :message="form.errors.code" />
      </div>

      <div v-else>
        <label for="recovery_code" class="block text-sm font-medium text-emerald-800 mb-1.5">Código de recuperación</label>
        <FwbInput
          id="recovery_code"
          ref="recoveryCodeInput"
          v-model="form.recovery_code"
          type="text"
          autocomplete="one-time-code"
        />
        <InputError class="mt-2" :message="form.errors.recovery_code" />
      </div>

      <div class="flex items-center justify-between pt-2">
        <button
          type="button"
          class="text-sm text-emerald-600 hover:text-emerald-800 underline"
          @click.prevent="toggleRecovery"
        >
          {{ recovery ? 'Usar código de autenticación' : 'Usar código de recuperación' }}
        </button>
        <FwbButton type="submit" color="green" :disabled="form.processing">
          Iniciar sesión
        </FwbButton>
      </div>
    </form>
  </AuthLayout>
</template>
