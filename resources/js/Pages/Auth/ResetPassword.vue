<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import InputError from '@/Components/InputError.vue'
import { useForm } from '@inertiajs/vue3'
import { FwbButton, FwbInput } from 'flowbite-vue'

const props = defineProps({
  email: String,
  token: String,
})

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.post(route('password.update'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <AuthLayout title="Nueva contraseña" subtitle="Elige una contraseña segura para tu cuenta">
    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-emerald-800 mb-1.5">Correo</label>
        <FwbInput v-model="form.email" type="email" required autocomplete="username" />
        <InputError class="mt-2" :message="form.errors.email" />
      </div>
      <div>
        <label class="block text-sm font-medium text-emerald-800 mb-1.5">Nueva contraseña</label>
        <FwbInput v-model="form.password" type="password" required autocomplete="new-password" />
        <InputError class="mt-2" :message="form.errors.password" />
      </div>
      <div>
        <label class="block text-sm font-medium text-emerald-800 mb-1.5">Confirmar contraseña</label>
        <FwbInput v-model="form.password_confirmation" type="password" required autocomplete="new-password" />
        <InputError class="mt-2" :message="form.errors.password_confirmation" />
      </div>
      <FwbButton type="submit" color="green" class="w-full" :disabled="form.processing">
        Restablecer contraseña
      </FwbButton>
    </form>
  </AuthLayout>
</template>
