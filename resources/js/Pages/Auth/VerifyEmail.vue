<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { FwbButton } from 'flowbite-vue'
import { computed } from 'vue'

const props = defineProps({ status: String })
const form = useForm({})

const submit = () => form.post(route('verification.send'))
const verificationLinkSent = computed(() => props.status === 'verification-link-sent')
</script>

<template>
  <AuthLayout title="Verificar correo" subtitle="Revisa tu bandeja de entrada">
    <p class="text-sm text-emerald-700/80 mb-4">
      Antes de continuar, verifica tu correo haciendo clic en el enlace que te enviamos.
      Si no lo recibiste, podemos enviarte otro.
    </p>

    <div v-if="verificationLinkSent" class="mb-4 p-3 rounded-lg bg-emerald-50 text-emerald-800 text-sm border border-emerald-200">
      Se envió un nuevo enlace de verificación a tu correo.
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <FwbButton type="submit" color="green" class="w-full" :disabled="form.processing">
        Reenviar correo de verificación
      </FwbButton>

      <div class="flex justify-center gap-4 text-sm pt-2">
        <Link :href="route('profile.show')" class="text-emerald-600 hover:text-emerald-800">
          Editar perfil
        </Link>
        <Link :href="route('logout')" method="post" as="button" class="text-emerald-600 hover:text-emerald-800">
          Cerrar sesión
        </Link>
      </div>
    </form>
  </AuthLayout>
</template>
