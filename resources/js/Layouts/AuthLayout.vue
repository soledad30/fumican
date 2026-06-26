<script setup>
import { Head, Link } from '@inertiajs/vue3'

defineProps({
  title: { type: String, default: 'Acceso' },
  subtitle: { type: String, default: '' },
})
</script>

<template>
  <Head :title="title" />
  <div class="auth-page min-h-screen flex flex-col">
    <header class="auth-header px-4 sm:px-6 py-4 flex justify-between items-center">
      <Link href="/" class="flex items-center gap-3 text-white hover:opacity-90 transition">
        <div class="auth-logo">
          <i class="fa-solid fa-paw"></i>
        </div>
        <span class="font-bold text-lg">Fumican Vet</span>
      </Link>
      <Link :href="route('welcome')" class="text-sm text-emerald-100/90 hover:text-white transition">
        <i class="fa-solid fa-house mr-1"></i> Inicio
      </Link>
    </header>

    <div class="flex-1 flex items-center justify-center p-4 sm:p-8">
      <div class="auth-card w-full max-w-md">
        <div class="text-center mb-8">
          <div class="auth-logo auth-logo--large mx-auto mb-4">
            <i class="fa-solid fa-paw text-2xl"></i>
          </div>
          <h1 class="text-2xl font-bold text-emerald-900">{{ title }}</h1>
          <p v-if="subtitle" class="text-emerald-700/70 mt-2 text-sm">{{ subtitle }}</p>
        </div>
        <slot />
      </div>
    </div>

    <footer class="text-center py-4 text-emerald-800/50 text-xs">
      © {{ new Date().getFullYear() }} Veterinaria Fumican
    </footer>
  </div>
</template>

<style scoped>
.auth-page {
  background: linear-gradient(160deg, #ecfdf5 0%, #d1fae5 40%, #ecfdf5 100%);
}
.auth-header {
  background: linear-gradient(90deg, #064e3b 0%, #047857 100%);
}
.auth-logo {
  @apply w-10 h-10 rounded-xl bg-white/15 border border-white/25 flex items-center justify-center text-white;
}
.auth-logo--large {
  @apply w-16 h-16 text-emerald-700 bg-emerald-100 border-emerald-200;
}
.auth-card {
  @apply bg-white rounded-2xl shadow-xl border border-emerald-100 p-8;
}
</style>
