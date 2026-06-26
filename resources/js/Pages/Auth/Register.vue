<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import InputError from '@/Components/InputError.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { FwbButton, FwbCheckbox, FwbInput } from 'flowbite-vue'

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  terms: false,
})

const submit = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <AuthLayout title="Crear cuenta" subtitle="Regístrate para acceder al sistema">
    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label for="name" class="block text-sm font-medium text-emerald-800 mb-1.5">Nombre</label>
        <FwbInput id="name" v-model="form.name" type="text" required autofocus autocomplete="name" />
        <InputError class="mt-2" :message="form.errors.name" />
      </div>

      <div>
        <label for="email" class="block text-sm font-medium text-emerald-800 mb-1.5">Correo electrónico</label>
        <FwbInput id="email" v-model="form.email" type="email" required autocomplete="username" />
        <InputError class="mt-2" :message="form.errors.email" />
      </div>

      <div>
        <label for="password" class="block text-sm font-medium text-emerald-800 mb-1.5">Contraseña</label>
        <FwbInput id="password" v-model="form.password" type="password" required autocomplete="new-password" />
        <InputError class="mt-2" :message="form.errors.password" />
      </div>

      <div>
        <label for="password_confirmation" class="block text-sm font-medium text-emerald-800 mb-1.5">Confirmar contraseña</label>
        <FwbInput id="password_confirmation" v-model="form.password_confirmation" type="password" required autocomplete="new-password" />
        <InputError class="mt-2" :message="form.errors.password_confirmation" />
      </div>

      <div v-if="$page.props.jetstream.hasTermsAndPrivacyPolicyFeature">
        <FwbCheckbox v-model="form.terms" name="terms" required>
          <span class="text-sm text-emerald-700">
            Acepto los
            <a target="_blank" :href="route('terms.show')" class="text-emerald-600 hover:text-emerald-800 underline">Términos</a>
            y la
            <a target="_blank" :href="route('policy.show')" class="text-emerald-600 hover:text-emerald-800 underline">Política de privacidad</a>
          </span>
        </FwbCheckbox>
        <InputError class="mt-2" :message="form.errors.terms" />
      </div>

      <div class="flex items-center justify-between pt-2">
        <Link :href="route('login')" class="text-sm text-emerald-600 hover:text-emerald-800">
          ¿Ya tienes cuenta?
        </Link>
        <FwbButton type="submit" color="green" :disabled="form.processing">
          Registrarse
        </FwbButton>
      </div>
    </form>
  </AuthLayout>
</template>
