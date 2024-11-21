<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div style="background-image: url('/fk-undip.jpg')" class="bg-cover bg-center">
        <div
            class="min-h-screen flex flex-col backdrop-blur-sm bg-white/30 sm:justify-center items-center pt-6 sm:pt-0 ">
            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden">
                <div class="flex justify-center mt-5 mb-10">
                    <div class="h-16 w-auto">
                        <Link href="/">
                        <ApplicationLogo />
                        </Link>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
