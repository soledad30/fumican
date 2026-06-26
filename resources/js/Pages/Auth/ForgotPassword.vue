<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import InputError from '@/Components/InputError.vue'
import { useForm } from '@inertiajs/vue3'
import { FwbButton, FwbInput } from 'flowbite-vue'

defineProps({ status: String })

const form = useForm({ email: '' })

const submit = () => form.post(route('password.email'))
</script>

<template>
  <AuthLayout title="Recuperar contraseña" subtitle="Te enviaremos un enlace para restablecer tu acceso">
    <p class="text-sm text-emerald-700/80 mb-4">
      Ingresa tu correo y recibirás un enlace para elegir una nueva contraseña.
    </p>

    <div v-if="status" class="mb-4 p-3 rounded-lg bg-emerald-50 text-emerald-800 text-sm border border-emerald-200">
      {{ status }}
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label for="email" class="block text-sm font-medium text-emerald-800 mb-1.5">Correo electrónico</label>
        <FwbInput id="email" v-model="form.email" type="email" required autofocus autocomplete="username" />
        <InputError class="mt-2" :message="form.errors.email" />
      </div>
      <FwbButton type="submit" color="green" class="w-full" :disabled="form.processing">
        Enviar enlace
      </FwbButton>
    </form>
  </AuthLayout>
</template>
