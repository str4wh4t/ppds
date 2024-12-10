<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/20/solid';
import { Head, router } from '@inertiajs/vue3';
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const exceededWorkloads = computed(() => {
    return usePage().props.exceededWorkloads;
});

const kaprodiUnits = computed(() => {
    return usePage().props.auth.user.kaprodi_units;
});

// Membuat array ownedExceededWorkloads
const ownedExceededWorkloads = exceededWorkloads.value.filter(workload =>
    kaprodiUnits.value.some(unit => unit.id === workload.user.student_unit_id)
);

const unreadConsults = computed(() => {
    return usePage().props.unreadConsults;
});

const dosbingStudents = computed(() => {
    return usePage().props.auth.user.dosbing_students;
});

// Membuat array ownedUnreadConsults
const ownedUnreadConsults = unreadConsults.value.filter(consult =>
    dosbingStudents.value.some(student => student.id === consult.user_id)
);

</script>

<template>

    <Head title="Notif" />
    <AuthenticatedLayout>
        <template #header>
            Notifications
        </template>
        <div v-if='!$hasRoles("student")'>
            <div v-if='ownedExceededWorkloads.length > 0'
                class="rounded-md bg-red-50 border-red-600 border-2 p-4  mt-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <XCircleIcon class="h-4 w-4 u text-red-400" aria-hidden="true" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Perhatian</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul v-for="exceededWorkload in ownedExceededWorkloads" :key="exceededWorkload.id"
                                role="list" class="list-disc space-y-1 pl-5">
                                <li>
                                    Mhs <span class="font-bold">{{ exceededWorkload.user.fullname }}</span> dari unit
                                    <span class="font-bold">{{
                                        exceededWorkload.user.student_unit.name }}</span>, Kelebihan
                                    beban
                                    kerja sebesar <span class="font-bold">{{
                                        exceededWorkload.workload_hours
                                    }}</span> jam
                                    pada minggu ke
                                    <span class="font-bold">{{ exceededWorkload.week }}</span> tahun
                                    <span class="font-bold">{{ exceededWorkload.year }}</span>
                                </li>
                            </ul>
                        </div>
                        <div class="mt-4">
                            <div class="-mx-2 -my-1.5 flex">
                                <Link :href="route('week-monitors.index', { user: usePage().props.auth.user })"
                                    method="get" as="button"
                                    class="rounded-md bg-red-100 border-2 border-red-600 px-2 py-1.5 text-sm font-medium text-red-800 hover:bg-red-200 focus:outline-none focus:ring-2">
                                Monitoring
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="rounded-md bg-green-50 border-green-600 border-2 p-4 mt-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <CheckCircleIcon class="h-4 w-4 u text-green-400" aria-hidden="true" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">Selamat</h3>
                        <div class="mt-2 text-sm text-green-700">
                            <p>Tidak ditemukan mahasiswa yang memiliki beban kerja yang melebihi aturan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-else>
            <div v-if='exceededWorkloads.length > 0' class="rounded-md bg-red-50 border-red-600 border-2 p-4  mt-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <XCircleIcon class="h-4 w-4 u text-red-400" aria-hidden="true" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Perhatian</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul v-for="exceededWorkload in exceededWorkloads" :key="exceededWorkload.id" role="list"
                                class="list-disc space-y-1 pl-5">
                                <li>
                                    Kelebihan beban kerja sebesar <span class="font-bold">{{
                                        exceededWorkload.workload_hours
                                        }}</span> jam
                                    pada minggu ke
                                    <span class="font-bold">{{ exceededWorkload.week }}</span> tahun
                                    <span class="font-bold">{{ exceededWorkload.year }}</span>
                                </li>
                            </ul>
                        </div>
                        <div class="mt-4">
                            <div class="-mx-2 -my-1.5 flex">
                                <Link :href="route('week-monitors.index', { user: usePage().props.auth.user })"
                                    method="get" as="button"
                                    class="rounded-md bg-red-100 border-2 border-red-600 px-2 py-1.5 text-sm font-medium text-red-800 hover:bg-red-200 focus:outline-none focus:ring-2">
                                Monitoring
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="rounded-md bg-green-50 border-green-600 border-2 p-4 mt-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <CheckCircleIcon class="h-4 w-4 u text-green-400" aria-hidden="true" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">Selamat</h3>
                        <div class="mt-2 text-sm text-green-700">
                            <p>Anda tidak memiliki beban kerja yang melebihi aturan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if='!$hasRoles("student")'>
            <div v-if='ownedUnreadConsults.length > 0' class="rounded-md bg-red-50 border-red-600 border-2 p-4  mt-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <XCircleIcon class="h-4 w-4 u text-red-400" aria-hidden="true" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Perhatian</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul v-for="unreadConsult in ownedUnreadConsults" :key="unreadConsult.id" role="list"
                                class="list-disc space-y-1 pl-5">
                                <li>
                                    Terdapat permintaan konsultasi dari Mhs <span class="font-bold">{{
                                        unreadConsult.user.fullname }}</span> dari unit
                                    <span class="font-bold">{{
                                        unreadConsult.user.student_unit.name }}</span>
                                </li>
                            </ul>
                        </div>
                        <div class="mt-4">
                            <div class="-mx-2 -my-1.5 flex">
                                <Link :href="route('consults.student-list')" method="get" as="button"
                                    class="rounded-md bg-red-100 border-2 border-red-600 px-2 py-1.5 text-sm font-medium text-red-800 hover:bg-red-200 focus:outline-none focus:ring-2">
                                Monitoring
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="rounded-md bg-green-50 border-green-600 border-2 p-4 mt-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <CheckCircleIcon class="h-4 w-4 u text-green-400" aria-hidden="true" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">Selamat</h3>
                        <div class="mt-2 text-sm text-green-700">
                            <p>Tidak ditemukan permintaan konsultasi mahasiswa.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-else>
            <div v-if='unreadConsults.length > 0' class="rounded-md bg-red-50 border-red-600 border-2 p-4  mt-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <XCircleIcon class="h-4 w-4 u text-red-400" aria-hidden="true" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Perhatian</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul v-for="unreadConsult in unreadConsults" :key="unreadConsult.id" role="list"
                                class="list-disc space-y-1 pl-5">
                                <li>
                                    Terdapat permintaan konsultasi dengan judul <span class="font-bold">{{
                                        unreadConsult.consult_title
                                        }}</span> belum di balas
                                </li>
                            </ul>
                        </div>
                        <div class="mt-4">
                            <div class="-mx-2 -my-1.5 flex">
                                <Link :href="route('consults.index', { user: usePage().props.auth.user })" method="get"
                                    as="button"
                                    class="rounded-md bg-red-100 border-2 border-red-600 px-2 py-1.5 text-sm font-medium text-red-800 hover:bg-red-200 focus:outline-none focus:ring-2">
                                Monitoring
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="rounded-md bg-green-50 border-green-600 border-2 p-4 mt-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <CheckCircleIcon class="h-4 w-4 u text-green-400" aria-hidden="true" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">Selamat</h3>
                        <div class="mt-2 text-sm text-green-700">
                            <p>Anda tidak memiliki permintaan konsultasi.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>