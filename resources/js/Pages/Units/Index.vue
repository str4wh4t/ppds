<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Link, usePage } from '@inertiajs/vue3';
import ModalCreate from './Modals/Create.vue';
import ModalUpdate from './Modals/Update.vue';
import ModalUploadSchedule from './Modals/UploadSchedule.vue';
import ModalUploadGuideline from './Modals/UploadGuideline.vue';
import CreateButton from '@/Components/CreateButton.vue';
import { CheckCircleIcon, ChevronLeftIcon, ChevronRightIcon, MagnifyingGlassIcon, XCircleIcon } from '@heroicons/vue/20/solid';
import { ref, computed } from 'vue';
import { CalendarDaysIcon, ClipboardDocumentListIcon, PencilSquareIcon } from '@heroicons/vue/24/outline';

const units = computed(() => usePage().props.units);

const filters = ref({ search: usePage().props.filters.search ?? '' });

const isUploadSchedule = ref(false);
const isUploadGuideline = ref(false);
const isCreate = ref(false);
const isUpdate = ref(false);
const selectedItem = ref({});

const openUpdate = (unit) => {
    selectedItem.value = unit;
    isUpdate.value = true;
};

const closeUpdate = () => {
    isUpdate.value = false;
};

const openUploadSchedule = (unit) => {
    selectedItem.value = unit;
    isUploadSchedule.value = true;
};

const closeUploadSchedule = () => {
    isUploadSchedule.value = false;
};

const openUploadGuideline = (unit) => {
    selectedItem.value = unit;
    isUploadGuideline.value = true;
};

const closeUploadGuideline = () => {
    isUploadGuideline.value = false;
};



const searchPosts = () => {
    router.get(route('units.index'), { search: filters.value.search }, { replace: true });
};

</script>

<template>

    <Head title="User List" />
    <AuthenticatedLayout>
        <template #header>
            Unit List
        </template>
        <div class="sm:flex sm:items-center mt-4">
            <div class="sm:flex-auto">
                <div class="relative rounded-md shadow-sm">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <MagnifyingGlassIcon class="h-4 w-4 u text-gray-400" aria-hidden="true" />
                    </div>
                    <input v-model="filters.search" @keyup.enter="searchPosts" type="text" placeholder="Pencarian data"
                        class="block w-full rounded-full border-0 py-1.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                </div>
            </div>
            <div class="mt-4 sm:ml-5 sm:mt-0 sm:flex-none">
                <CreateButton @click="isCreate = true">
                    Add unit
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
                            Kaprodi</th>
                        <th scope="col" class="px-3 py-2 text-left text-sm font-semibold text-gray-900 lg:table-cell">
                            Admin</th>
                        <th scope="col" class="hidden px-3 py-2 text-left text-sm font-semibold text-gray-900 lg:table-cell">
                            Stase</th>
                        <th scope="col" class="hidden px-3 py-2 text-sm font-semibold text-gray-900 lg:table-cell">
                            Pedoman</th>
                        <!-- <th scope="col"
                            class="hidden px-3 py-2 text-left text-sm font-semibold text-gray-900 lg:table-cell">
                            CreatedAt</th> -->
                        <th scope="col" class="relative py-1 pl-3 pr-4 sm:pr-6">
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(unit, index) in units.data" :key="unit.id">
                        <td
                            :class="[index === 0 ? '' : 'border-t border-transparent', 'relative py-1 pl-4 pr-3 text-sm sm:pl-6']">
                            <div class="font-medium text-gray-900">
                                {{ unit.name }}
                            </div>
                            <div class="mt-1 flex flex-col text-gray-500 sm:block lg:hidden">
                                <span>{{ unit.kaprodi_user?.fullname ?? '' }}</span>
                            </div>
                            <div v-if="index !== 0" class="absolute -top-px left-6 right-0 h-px bg-gray-200" />
                        </td>
                        <td
                            :class="[index === 0 ? '' : 'border-t border-gray-200', 'hidden px-3 py-2 text-sm text-gray-500 lg:table-cell']">
                            {{ unit.kaprodi_user?.fullname ?? '' }}</td>
                        <td
                            :class="[index === 0 ? '' : 'border-t border-gray-200', 'px-3 py-2 text-sm text-gray-500 lg:table-cell']">
                            {{ (unit?.unit_admins ?? []).map(admin => admin.fullname).join(', ') }}</td>
                        <td :class="[index === 0 ? '' : 'border-t border-gray-200', 'hidden px-3 py-2 text-sm text-gray-500 lg:table-cell']">
                            <ul v-if="$hasItems(unit?.stases)" class="list-disc">
                                <li v-for="stase in unit?.stases ?? []" :key="stase.name" class="whitespace-nowrap">
                                    {{ stase.name }}
                                    <div v-if="$hasItems(stase.locations)" class="text-xs">
                                        <hr class="border-t border-gray-400 my-0"> 
                                        {{ (stase.locations ?? []).map(location => location.name).join(', ') }}
                                    </div>
                                </li>
                            </ul>

                        </td>
                        <td
                            :class="[index === 0 ? '' : 'border-t border-gray-200', 'hidden px-3 py-2 text-sm text-gray-500 lg:table-cell']">
                            <div class="flex justify-center items-center">
                                <CheckCircleIcon v-if="unit.guideline_document_path"
                                    class="h-4 w-4 u text-green-400 cursor-pointer" @click="openUploadGuideline(unit)"
                                    aria-hidden="true" />
                                <XCircleIcon v-else class="h-4 w-4 u text-red-400 cursor-pointer"
                                    @click="openUploadGuideline(unit)" aria-hidden="true" />
                            </div>
                        </td>
                        <!-- <td
                            :class="[index === 0 ? '' : 'border-t border-gray-200', 'hidden px-3 py-2 text-sm text-gray-500 lg:table-cell']">
                            {{ $formatDate({ date: unit.created_at }) }}</td> -->
                        <td
                            :class="[index === 0 ? '' : 'border-t border-transparent', 'relative py-1 pl-3 pr-4 text-center text-sm font-medium sm:pr-6']">
                            <span v-if="$hasItems(unit?.stases)" class="isolate inline-flex rounded-md shadow-sm">
                                <button type="button" @click="openUpdate(unit)" class="relative inline-flex items-center rounded-l-md bg-white px-2 py-2 ring-1
                                    ring-inset ring-gray-300 hover:bg-gray-50 focus:z-10">
                                    <span class="sr-only">Edit</span>
                                    <PencilSquareIcon class="h-4 w-4 u" aria-hidden="true" />
                                </button>
                                <Link :href="route('units.stases', { unit: unit })"
                                    class="relative -ml-px inline-flex items-center rounded-r-md bg-white px-2 py-2 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-10"
                                    as="button">
                                <span class="sr-only">Stases</span>
                                <ClipboardDocumentListIcon class="h-4 w-4 u" aria-hidden="true" />
                                </Link>
                            </span>
                            <button v-else type="button" @click="openUpdate(unit)"
                                class="inline-flex items-center rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-white">
                                Edit<span class="sr-only">, {{ unit.name }}</span>
                            </button>
                            <div v-if="index !== 0" class="absolute -top-px left-0 right-6 h-px bg-gray-200" />
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6">
                <div class="flex flex-1 justify-between sm:hidden">
                    <Link :href="!units.prev_page_url ? '#' : units.prev_page_url"
                        class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        as="button">
                    Previous
                    </Link>

                    <Link :href="!units.next_page_url ? '#' : units.next_page_url"
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
                            <span class="font-medium">{{ units.from }}</span>
                            {{ ' ' }}
                            to
                            {{ ' ' }}
                            <span class="font-medium">{{ units.to }}</span>
                            {{ ' ' }}
                            of
                            {{ ' ' }}
                            <span class="font-medium">{{ units.total }}</span>
                            {{ ' ' }}
                            results
                        </p>
                    </div>
                    <div>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                            <Link :href="!units.prev_page_url ? '#' : units.prev_page_url"
                                class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
                                as="button">
                            <ChevronLeftIcon class="h-4 w-4 u" aria-hidden="true" />
                            </Link>
                            <Link
                                v-for="link in units.links.filter((link, index) => index !== 0 && index !== units.links.length - 1)"
                                :key="link.label" :href="!link.url ? '#' : link.url"
                                class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 focus:z-20 focus:outline-offset-0"
                                :class="[link.label == units.current_page ? 'z-10 bg-indigo-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600' : 'ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-offset-0']"
                                :disabled="link.label == units.current_page" as="button" v-html="link.label">
                            </Link>
                            <Link :href="!units.next_page_url ? '#' : units.next_page_url"
                                class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
                                as="button">
                            <ChevronRightIcon class="h-4 w-4 u" aria-hidden="true" />
                            </Link>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <ModalCreate :show="isCreate" @close="isCreate = false" />
        <ModalUpdate :unit="selectedItem" :show="isUpdate" @close="closeUpdate" @exitUpdate="closeUpdate" />
        <ModalUploadSchedule :unit="selectedItem" :show="isUploadSchedule" @close="closeUploadSchedule"
            @exitUpdate="closeUploadSchedule" />
        <ModalUploadGuideline :unit="selectedItem" :show="isUploadGuideline" @close="closeUploadGuideline"
            @exitUpdate="closeUploadGuideline" />
    </AuthenticatedLayout>
</template>