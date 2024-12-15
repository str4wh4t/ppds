<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import ModalCreate from './Modals/Create.vue';
import ModalUpdate from './Modals/Update.vue';
import ModalAnswer from './Modals/Answer.vue';
import ModalDocument from '@/Components/ModalDocument.vue';
import CreateButton from '@/Components/CreateButton.vue';
import { CheckCircleIcon, ChevronLeftIcon, ChevronRightIcon, MagnifyingGlassIcon, XCircleIcon } from '@heroicons/vue/20/solid';
import { ref, computed } from 'vue';
import { StarIcon } from '@heroicons/vue/20/solid'
import { PaperClipIcon } from '@heroicons/vue/24/outline';

const products = [
    {
        id: 1,
        href: 'https://gazebu-fk.apps.undip.ac.id',
        imageSrc: '/flyers/gazebu-fk-784-1168.jpg',
        imageAlt: "GAZEBU FK UNDIP",
    },
    {
        id: 2,
        href: 'https://curcol-fk.apps.undip.ac.id',
        imageSrc: '/flyers/curcol-fk-784-1168.jpg',
        imageAlt: "CURCOL FK UNDIP",
    },
    {
        id: 3,
        href: 'https://petik.rskariadi.id',
        imageSrc: '/flyers/sipetik-rskariadi-784-1168.jpg',
        imageAlt: "SIPETIK RS KARIADI",
    },
    {
        id: 4,
        href: 'https://wa.me/6282325051078',
        imageSrc: '/flyers/helpline-rskariadi-784-1168.jpg',
        imageAlt: "HELP LINE RS KARIADI",
    },
]

</script>

<template>

    <Head title="Speak List" />
    <AuthenticatedLayout>
        <template #header>
            Daftar Laporan
        </template>
        <div class="sm:flex sm:items-center mt-4">
            <div class="grid grid-cols-1 gap-y-10 sm:grid-cols-2 sm:gap-x-6 sm:gap-y-0 lg:gap-x-8">
                <div v-for="product in products" :key="product.id" class="group relative">
                    <div
                        class="h-96 w-full mb-5 overflow-hidden rounded-lg sm:aspect-h-3 sm:aspect-w-2 group-hover:opacity-75 sm:h-auto">
                        <a target="_blank" :href="product.href">
                            <img :src="product.imageSrc" :alt="product.imageAlt"
                                class="h-full w-full object-cover object-center" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>