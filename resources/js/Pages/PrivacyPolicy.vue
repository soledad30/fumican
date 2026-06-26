<script setup>
import { Head, Link } from '@inertiajs/vue3'

defineProps({ policy: String })
</script>

<template>
  <Head title="Política de privacidad" />
  <div class="min-h-screen auth-page-bg">
    <header class="auth-header px-4 sm:px-6 py-4 flex justify-between items-center">
      <Link href="/" class="flex items-center gap-3 text-white hover:opacity-90 transition">
        <div class="w-10 h-10 rounded-xl bg-white/15 border border-white/25 flex items-center justify-center">
          <i class="fa-solid fa-paw"></i>
        </div>
        <span class="font-bold text-lg">Fumican Vet</span>
      </Link>
    </header>
    <div class="max-w-2xl mx-auto px-4 py-8">
      <div class="bg-white rounded-2xl shadow-xl border border-emerald-100 p-6 sm:p-8 prose prose-emerald max-w-none" v-html="policy" />
    </div>
  </div>
</template>

<style scoped>
.auth-page-bg {
  background: linear-gradient(160deg, #ecfdf5 0%, #d1fae5 50%, #ecfdf5 100%);
}
.auth-header {
  background: linear-gradient(90deg, #064e3b 0%, #047857 100%);
}
</style>
