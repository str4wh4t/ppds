<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import ModalDocument from '@/Components/ModalDocument.vue';
import CreateButton from '@/Components/CreateButton.vue';
import { CheckCircleIcon, ChevronLeftIcon, ChevronRightIcon, MagnifyingGlassIcon, XCircleIcon } from '@heroicons/vue/20/solid';
import { ref, computed } from 'vue';
import { StarIcon } from '@heroicons/vue/20/solid'
import { PaperClipIcon } from '@heroicons/vue/24/outline';

const surverys = [
    {
        id: 1,
        href: 'https://drive.google.com/drive/u/2/folders/1_eujru0YBdfC7p_1bPqXTp8zFkx9Xf39',
        text: "Skrining Depresi Berkala",
    },
    {
        id: 2,
        href: 'https://drive.google.com/drive/u/2/folders/13RcheQWzYUdo1b5Xr2_ZVdMOSyPbAplU',
        text: "Evaluasi Kinerja 360 Derajat",
    },
    {
        id: 3,
        href: 'https://drive.google.com/drive/u/2/folders/1d5H5SzHddIoBkeNnafWiIRt5XSS51kOQ',
        text: "Identifikasi Perundungan ",
    },
    {
        id: 4,
        href: 'https://docs.google.com/forms/d/e/1FAIpQLScsd6gT3G0PHRltWUuz2bWyViEr679YnUsBP24sWvvOU8XrQQ/viewform?usp=sharing',
        text: "Internalisasi Perundungan ",
    },
]

</script>

<template>

    <Head title="Speak List" />
    <AuthenticatedLayout>
        <template #header>
            Daftar Survey
        </template>
        <div class="sm:flex sm:items-center mt-4">
            <div class="grid grid-cols-1 items-center gap-x-8 gap-y-16 lg:grid-cols-2">
                <div class="mx-auto w-full max-w-xl lg:mx-0">
                    <h2 class="text-pretty text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl">Daftar Survey</h2>
                    <p class="mt-6 text-lg/8 text-gray-600">Berikut adalah daftar survey yang harus di isi oleh setiap mahasiswa ppds dan ppdss sebagai syarat pindah ke semester baru dan sebagai monev dari Irjen Kemkes dan Kemendikti.</p>
                </div>
                <div class="mx-auto grid w-full max-w-xl grid-cols-2 items-center gap-x-4 gap-y-4 lg:mx-0 lg:max-w-none lg:pl-8">
                    <div v-for="survey in surverys" :key="survey.id">
                        <a :href="survey.href" target="_blank" class="bg-gray-400/5 p-8 sm:p-5 border-4 border-red-50 block cursor-pointer hover:bg-sky-100 hover:border-red-100">
                            <span >{{ survey.text }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>