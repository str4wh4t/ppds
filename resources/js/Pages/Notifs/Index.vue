<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/20/solid';
import { Head, router } from '@inertiajs/vue3';
import { Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';


</script>

<template>

    <Head title="Notif" />
    <AuthenticatedLayout>
        <template #header>
            Notifications
        </template>
        <div v-if="true" class="rounded-md bg-red-50 p-4 border-red-600 border-2">
            <div class="flex">
                <div class="flex-shrink-0">
                    <XCircleIcon class="h-5 w-5 text-red-400" aria-hidden="true" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Perhatian</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul role="list" class="list-disc space-y-1 pl-5">
                            <li>Terdapat <span class="font-bold">2</span>
                                mahasiwa yang
                                dalam
                                keadaan
                                yang lebih
                                baik
                                dialihkan ke waktu lain</li>
                            <li>Terdapat <span class="font-bold">1</span>
                                mahasiwa
                                yang dalam keadaan yang tidak boleh dijadwalkan lagi</li>
                        </ul>
                    </div>
                    <div class="mt-4">
                        <div class="-mx-2 -my-1.5 flex">
                            <Link :href="route('notifs.index')" method="get" as="button"
                                class="rounded-md bg-red-100 border-2 border-red-600 px-2 py-1.5 text-sm font-medium text-red-800 hover:bg-red-200 focus:outline-none focus:ring-2">
                            Monitoring
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="rounded-md bg-green-50 border-green-600 border-2 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <CheckCircleIcon class="h-5 w-5 text-green-400" aria-hidden="true" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-green-800">Selamat</h3>
                    <div class="mt-2 text-sm text-green-700">
                        <p>Tidak ditemukan mahasiswa yang memiliki beban kerja yang melebihi aturan.</p>
                    </div>
                    <div class="mt-4">
                        <div class="-mx-2 -my-1.5 flex">
                            <Link :href="route('notifs.index')" method="get" as="button"
                                class="rounded-md bg-green-100 border-2 border-green-600 px-2 py-1.5 text-sm font-medium text-green-800 hover:bg-green-200 focus:outline-none focus:ring-2">
                            Monitoring
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>