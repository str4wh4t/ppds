<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, Head, usePage } from '@inertiajs/vue3';
import { PaperClipIcon, UserCircleIcon } from '@heroicons/vue/20/solid'
import { PlusCircleIcon } from '@heroicons/vue/24/outline';
import ModalGuideline from './Modals/Guideline.vue';
import { ref, computed } from 'vue';

const user = usePage().props.auth.user;
const showGuideline = ref(false);

const closeGuideline = () => {
    showGuideline.value = false;
};

const is_student = user.roles?.some((role) =>
    ['student'].includes(role.name)
);

if (!user.is_read_guideline && is_student) {
    showGuideline.value = true;
}

</script>

<template>

    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            Dashboard
        </template>
        <div>
            <div class="px-4 sm:px-0 mt-4">
                <h2 class="text-lg font-medium text-gray-900 mt-4">Informasi pengguna</h2>
                <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-500">Detail dari informasi pengguna dan peran
                    didalam sistem.</p>
            </div>
            <div class="mt-4">
                <dl class="grid grid-cols-2">
                    <div class="border-t border-gray-100 px-4 py-3 sm:col-span-1 sm:px-0">
                        <dt class="text-sm font-medium leading-6 text-gray-900">Nama lengkap</dt>
                        <dd class="mt-1 text-sm leading-6 text-gray-700 sm:mt-2">{{ user.fullname }}
                        </dd>
                    </div>
                    <div class="border-t border-gray-100 px-4 py-3 sm:col-span-1 sm:px-0">
                        <dt class="text-sm font-medium leading-6 text-gray-900">Username</dt>
                        <dd class="mt-1 text-sm leading-6 text-gray-700 sm:mt-2">{{ user.username }}
                        </dd>
                    </div>
                    <div class="border-t border-gray-100 px-4 py-3 sm:col-span-1 sm:px-0">
                        <dt class="text-sm font-medium leading-6 text-gray-900">Identitas</dt>
                        <dd class="mt-1 text-sm leading-6 text-gray-700 sm:mt-2">{{ user.identity }}
                        </dd>
                    </div>
                    <div class="border-t border-gray-100 px-4 py-3 sm:col-span-1 sm:px-0">
                        <dt class="text-sm font-medium leading-6 text-gray-900">Alamat email</dt>
                        <dd class="mt-1 text-sm leading-6 text-gray-700 sm:mt-2">{{ user.email }}</dd>
                    </div>
                    <div v-if="$hasRoles('student')" class="border-t border-gray-100 px-4 py-3 sm:col-span-1 sm:px-0">
                        <dt class="text-sm font-medium leading-6 text-gray-900">Prodi</dt>
                        <dd class="mt-1 text-sm leading-6 text-gray-700 sm:mt-2">{{ user.student_unit.name }}</dd>
                    </div>
                    <!-- <div class="border-t border-gray-100 px-4 py-3 sm:col-span-2 sm:px-0">
                        <dt class="text-sm font-medium leading-6 text-gray-900">About</dt>
                        <dd class="mt-1 text-sm leading-6 text-gray-700 sm:mt-2">Fugiat ipsum ipsum deserunt culpa
                            aute sint do nostrud anim incididunt cillum culpa consequat. Excepteur qui ipsum aliquip
                            consequat sint. Sit id mollit nulla mollit nostrud in ea officia proident. Irure nostrud
                            pariatur mollit ad adipisicing reprehenderit deserunt qui eu.</dd>
                    </div> -->
                    <div class="mt-4 px-4 py-3 text-center col-span-2 sm:px-0 block lg:hidden">
                        <Link :href="route('activities.calendar', { user: user })"
                            class="inline-flex justify-center gap-x-1.5 rounded-full bg-indigo-600 w-full px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        <PlusCircleIcon class="-ml-0.5 h-4 w-4 u" aria-hidden="true" />
                        Tambah aktifitas
                        </Link>
                    </div>
                    <div class="border-t mt-4 border-gray-100 px-4 py-3 col-span-2 sm:px-0">
                        <dt class="text-sm font-medium leading-6 text-gray-900">Peran pengguna</dt>
                        <dd class="mt-2 text-sm text-gray-900">
                            <ul role="list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <li v-for="item in user.roles" :key="item.name"
                                    class="divide-y divide-gray-100 rounded-md border border-gray-200">
                                    <div class="flex items-center justify-between py-3 pl-4 pr-5 text-sm leading-6">
                                        <div class="flex w-0 flex-1 items-center">
                                            <UserCircleIcon class="h-4 w-4 u flex-shrink-0 text-gray-400"
                                                aria-hidden="true" />
                                            <div class="ml-4 flex min-w-0 flex-1 gap-2">
                                                <span class="truncate font-medium">
                                                    <Link href="#"
                                                        :class="[item.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-600', 'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6']"
                                                        :method="item.method" as="button">
                                                    {{ item.name }}
                                                    </Link>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
        <ModalGuideline :user="user" :show="showGuideline" @close="closeGuideline" @exitUpdate="closeGuideline" />
    </AuthenticatedLayout>
</template>
