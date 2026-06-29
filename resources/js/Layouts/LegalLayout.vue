<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div style="background-image: url('/fk-undip.jpg')" class="bg-cover bg-center min-h-screen">
        <div class="min-h-screen backdrop-blur-sm bg-white/40 py-8 px-4 sm:px-6">
            <div class="mx-auto w-full max-w-3xl rounded-lg bg-white shadow-md overflow-hidden">
                <div class="flex justify-center border-b border-gray-100 px-6 py-6">
                    <Link href="/" class="h-14 w-auto">
                        <ApplicationLogo />
                    </Link>
                </div>
                <div class="px-6 py-8 sm:px-10">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
