<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 auth-page-bg">
        <div>
            <slot name="logo" />
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-xl border border-emerald-100 overflow-hidden sm:rounded-2xl">
            <slot />
        </div>
    </div>
</template>

<style scoped>
.auth-page-bg {
    background: linear-gradient(160deg, #ecfdf5 0%, #d1fae5 50%, #ecfdf5 100%);
}
</style>
