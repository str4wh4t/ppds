<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Link, usePage } from '@inertiajs/vue3';
import ModalPickDosen from './Modals/Stases/PickDosen.vue';
import CreateButton from '@/Components/CreateButton.vue';
import { useModalStore } from '@/stores/modalStore';
import { ChevronLeftIcon, ChevronRightIcon, MagnifyingGlassIcon } from '@heroicons/vue/20/solid';
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/20/solid';
import { ref, computed } from 'vue';
import { ArrowLeftCircleIcon } from '@heroicons/vue/24/outline';

const modalStore = useModalStore();

const stases = computed(() => usePage().props.stases);
const unit = ref(usePage().props.unit);

const filters = ref({ search: usePage().props.filters.search ?? '' });

const isCreate = ref(false);
const isUpdate = ref(false);
const selectedItem = ref([]);

const openUpdate = (stase) => {
    selectedItem.value = stase;
    isUpdate.value = true;
};

const closeUpdate = () => {
    isUpdate.value = false;
};

const searchPosts = () => {
    router.get(route('units.stases', { unit: usePage().props.unit }), { search: filters.value.search }, { replace: true });
};

const openModalGlobal = () => {
    modalStore.openModal('Warning', 'Silahkan dapat menambahkan stase melalui menu unit');
};

</script>

<template>

    <Head title="Stase List" />
    <AuthenticatedLayout>
        <template #header>
            Stase List
        </template>
        <div class="-mx-4 ring-1 ring-gray-300 sm:mx-0 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
                <thead>
                    <tr>
                        <td scope="col"
                            class="max-w-5 py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                            Unit</td>
                        <td scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 lg:table-cell">
                            : {{ unit.name }}</td>
                    </tr>
                    <tr>
                        <td scope="col"
                            class="max-w-5 border-t border-gray-200 py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                            Kaprodi</td>
                        <td scope="col"
                            class="border-t border-gray-200 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 lg:table-cell">
                            : {{ unit.kaprodi_user?.fullname ?? '' }}</td>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="sm:flex sm:items-center mt-5">
            <div class="mt-4 sm:mt-0 sm:flex-none">
                <Link :href="route('units.index')"
                    class="inline-flex items-center gap-x-1.5 rounded-md bg-yellow-400 px-3 py-2 text-sm font-semibold border-2 border-yellow-600 shadow-sm hover:bg-yellow-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-yellow-600">
                <ArrowLeftCircleIcon class="h-5 w-5" aria-hidden="true" />
                Back
                </Link>
            </div>
            <div class="sm:ml-5 sm:flex-auto">
                <div class="relative rounded-md shadow-sm">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
                    </div>
                    <input v-model="filters.search" @keyup.enter="searchPosts" type="text" placeholder="Pencarian data"
                        class="block w-full rounded-full border-0 py-1.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                </div>
            </div>
            <div class="mt-4 sm:ml-5 sm:mt-0 sm:flex-none">
                <CreateButton @click="openModalGlobal()">
                    Add stase
                </CreateButton>
            </div>
        </div>
        <div class="-mx-4 mt-5 ring-1 ring-gray-300 sm:mx-0 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
                <thead>
                    <tr>
                        <th scope="col" class="py-2 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                            Name
                        </th>
                        <th scope="col"
                            class="hidden px-3 py-2 text-left text-sm font-semibold text-gray-900 lg:table-cell">
                            Location</th>
                        <th scope="col"
                            class="hidden px-3 py-2 text-left text-sm font-semibold text-gray-900 lg:table-cell">
                            Dosen</th>
                        <th scope="col"
                            class="hidden px-3 py-2 text-center text-sm font-semibold text-gray-900 lg:table-cell">
                            IsMandtry</th>
                        <th scope="col"
                            class="hidden px-3 py-2 text-left text-sm font-semibold text-gray-900 lg:table-cell">
                            CreatedAt</th>
                        <th scope="col" class="relative py-1 pl-3 pr-4 sm:pr-6">
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(stase, index) in stases.data" :key="stase.id">
                        <td
                            :class="[index === 0 ? '' : 'border-t border-transparent', 'relative py-1 pl-4 pr-3 text-sm sm:pl-6']">
                            <div class="font-medium text-gray-900">
                                {{ stase.name }}
                            </div>
                            <div class="mt-1 flex flex-col text-gray-500 sm:block lg:hidden">
                                <span>{{ stase.location }}</span>
                            </div>
                            <div v-if="index !== 0" class="absolute -top-px left-6 right-0 h-px bg-gray-200" />
                        </td>
                        <td
                            :class="[index === 0 ? '' : 'border-t border-gray-200', 'hidden px-3 py-2 text-sm text-gray-500 lg:table-cell']">
                            {{ stase.location }}</td>
                        <td
                            :class="[index === 0 ? '' : 'border-t border-gray-200', 'hidden px-3 py-2 text-sm text-gray-500 lg:table-cell']">
                            <div class="sm:hidden">{{ (stase.unit_stases[0]?.users ?? []).map(user =>
                                user.fullname).join(', ')
                                }}
                            </div>
                            <div class="hidden sm:block">
                                {{ (stase.unit_stases[0]?.users ?? []).map(user => user.fullname).join(', ') }}
                            </div>
                        </td>
                        <td
                            :class="[index === 0 ? '' : 'border-t border-gray-200', 'hidden px-3 py-2 text-sm text-center text-gray-500 lg:table-cell']">
                            <div class="flex justify-center items-center">
                                <CheckCircleIcon v-if="stase.unit_stases[0].is_mandatory" class="h-5 w-5 text-green-400"
                                    aria-hidden="true" />
                                <XCircleIcon v-else class="h-5 w-5 text-red-400" aria-hidden="true" />
                            </div>
                        </td>
                        <td
                            :class="[index === 0 ? '' : 'border-t border-gray-200', 'hidden px-3 py-2 text-sm text-gray-500 lg:table-cell']">
                            {{ $formatDate({ date: stase.created_at }) }}</td>
                        <td
                            :class="[index === 0 ? '' : 'border-t border-transparent', 'relative py-1 pl-3 pr-4 text-center text-sm font-medium sm:pr-6']">
                            <button type="button" @click="openUpdate(stase)"
                                class="inline-flex items-center rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-white">
                                Edit<span class="sr-only">, {{ stase.name }}</span>
                            </button>
                            <div v-if="index !== 0" class="absolute -top-px left-0 right-6 h-px bg-gray-200" />
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6">
                <div class="flex flex-1 justify-between sm:hidden">
                    <Link :href="!stases.prev_page_url ? '#' : stases.prev_page_url"
                        class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        as="button">
                    Previous
                    </Link>

                    <Link :href="!stases.next_page_url ? '#' : stases.next_page_url"
                        class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        as="button">
                    Next
                    </Link>

                    <!-- <a href="#"
                        class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Previous</a>
                    <a href="#"
                        class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Next</a> -->
                </div>
                <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing
                            {{ ' ' }}
                            <span class="font-medium">{{ stases.from }}</span>
                            {{ ' ' }}
                            to
                            {{ ' ' }}
                            <span class="font-medium">{{ stases.to }}</span>
                            {{ ' ' }}
                            of
                            {{ ' ' }}
                            <span class="font-medium">{{ stases.total }}</span>
                            {{ ' ' }}
                            results
                        </p>
                    </div>
                    <div>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                            <Link :href="!stases.prev_page_url ? '#' : stases.prev_page_url"
                                class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
                                as="button">
                            <ChevronLeftIcon class="h-5 w-5" aria-hidden="true" />
                            </Link>
                            <Link
                                v-for="link in stases.links.filter((link, index) => index !== 0 && index !== stases.links.length - 1)"
                                :key="link.label" :href="!link.url ? '#' : link.url"
                                class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 focus:z-20 focus:outline-offset-0"
                                :class="[link.label == stases.current_page ? 'z-10 bg-indigo-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600' : 'ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-offset-0']"
                                :disabled="link.label == stases.current_page" as="button" v-html="link.label">
                            </Link>
                            <Link :href="!stases.next_page_url ? '#' : stases.next_page_url"
                                class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
                                as="button">
                            <ChevronRightIcon class="h-5 w-5" aria-hidden="true" />
                            </Link>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    <ModalPickDosen :stase="selectedItem" :show="isUpdate" @close="closeUpdate" @exitUpdate="closeUpdate" />
</template>