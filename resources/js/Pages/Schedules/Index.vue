<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Link, usePage } from '@inertiajs/vue3';
import ModalUploadSchedule from './Modals/UploadSchedule.vue';
import { CheckCircleIcon, ChevronLeftIcon, ChevronRightIcon, MagnifyingGlassIcon, XCircleIcon } from '@heroicons/vue/20/solid';
import { ref, computed, watch } from 'vue';
import { ArrowLeftCircleIcon } from '@heroicons/vue/24/outline';
import SelectInput from '@/Components/SelectInputBasic.vue';
import moment from 'moment';


const schedules = computed(() => usePage().props.schedules);

const isUploadSchedule = ref(false);
const selectedItem = ref({});

const openUploadSchedule = (schedule) => {
    selectedItem.value = schedule;
    isUploadSchedule.value = true;
};

const closeUploadSchedule = () => {
    isUploadSchedule.value = false;
};

const startYear = 2024;
const endYear = 2029;

// Membuat daftar tahun sebagai array of objects
const yearList = Array.from({ length: endYear - startYear + 1 }, (_, i) => ({
    id: i + 1,
    name: String(startYear + i),
}));
const unitList = usePage().props.units;

// Mengambil nilai yearSelected dari props
const yearSelected = ref(usePage().props.filters.yearSelected);

// Menemukan objek tahun yang sesuai dengan yearSelected
const yearSelectedOpt = ref(yearList.find(year => year.name == yearSelected.value) || null);

const unitSelected = ref(usePage().props.filters.unit);
const unitSelectedOpt = ref(unitList.find(unit => unit.name == unitSelected.value.name) || null);

watch(
    [() => yearSelectedOpt.value, () => unitSelectedOpt.value], // Properti reactive
    ([newYear, newUnit], [oldYear, oldUnit]) => {
        // Cek apakah ada perubahan pada salah satu properti
        if (newYear || newUnit) {
            router.get(
                route('schedules.index', { unit: newUnit?.id || null }), // Gunakan nilai terbaru
                { yearSelected: newYear?.name || null }, // Kirim nilai terbaru
                { replace: true }
            );
        }
    }
);

</script>

<template>

    <Head title="User List" />
    <AuthenticatedLayout>
        <template #header>
            Schedule List
        </template>
        <div class="sm:flex sm:items-center mt-4">
            <div class="divide-y divide-gray-100 rounded-md border border-red-400 px-5 py-2 sm:flex-auto">
                Tahun : <span class="font-bold">{{ yearSelected ?? usePage().props.currentYear }}</span>
            </div>
            <div class="mt-4 sm:ml-5 sm:mt-0 sm:flex-initial w-48">
                <SelectInput id="yearSelected" class="block w-full" v-model="yearSelectedOpt" :options="yearList"
                    required autofocus autocomplete="yearSelected" placeholder="Select year" />
            </div>
            <div class="mt-4 sm:ml-5 sm:mt-0 sm:flex-auto">
                <SelectInput id="unitSelected" class="block w-full" v-model="unitSelectedOpt" :options="unitList"
                    required autofocus autocomplete="unitSelected" placeholder="Select unit" />
            </div>
        </div>
        <div class="-mx-4 mt-5 ring-1 ring-gray-300 sm:mx-0 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
                <thead>
                    <tr>
                        <th scope="col" class="py-2 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                            Month
                        </th>
                        <th scope="col"
                            class="hidden px-3 py-2 text-center text-sm font-semibold text-gray-900 lg:table-cell">
                            Status</th>
                        <th scope="col" class="px-3 py-2 text-left text-sm font-semibold text-gray-900 lg:table-cell">
                            UpdatedAt</th>
                        <th scope="col" class="relative py-1 pl-3 pr-4 sm:pr-6">
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(schedule, index) in schedules.data" :key="schedule.id">
                        <td
                            :class="[index === 0 ? '' : 'border-t border-transparent', 'relative py-1 pl-4 pr-3 text-sm sm:pl-6']">
                            <div class="font-medium text-gray-900">
                                {{ schedule.month_name }}
                            </div>
                            <div v-if="index !== 0" class="absolute -top-px left-6 right-0 h-px bg-gray-200" />
                        </td>
                        <td
                            :class="[index === 0 ? '' : 'border-t border-gray-200', 'hidden px-3 py-2 text-sm text-gray-500 lg:table-cell']">
                            <div class="flex justify-center items-center">
                                <CheckCircleIcon v-if="schedule.document_path"
                                    class="h-4 w-4 u text-green-400 cursor-pointer"
                                    @click="openUploadSchedule(schedule)" aria-hidden="true" />
                                <XCircleIcon v-else class="h-4 w-4 u text-red-400 cursor-pointer"
                                    @click="openUploadSchedule(schedule)" aria-hidden="true" />
                            </div>
                        </td>
                        <td
                            :class="[index === 0 ? '' : 'border-t border-gray-200', 'hidden px-3 py-2 text-sm text-gray-500 lg:table-cell']">
                            {{ $formatDate({ date: schedule.updated_at }) }}</td>
                        <td
                            :class="[index === 0 ? '' : 'border-t border-transparent', 'relative py-1 pl-3 pr-4 text-center text-sm font-medium sm:pr-6']">
                            <button type="button" @click="openUploadSchedule(schedule)"
                                class="inline-flex items-center rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-white">
                                Edit<span class="sr-only">, {{ schedule.name }}</span>
                            </button>
                            <div v-if="index !== 0" class="absolute -top-px left-0 right-6 h-px bg-gray-200" />
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6">
                <div class="flex flex-1 justify-between sm:hidden">
                    <Link :href="!schedules.prev_page_url ? '#' : schedules.prev_page_url"
                        class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        as="button">
                    Previous
                    </Link>

                    <Link :href="!schedules.next_page_url ? '#' : schedules.next_page_url"
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
                            <span class="font-medium">{{ schedules.from }}</span>
                            {{ ' ' }}
                            to
                            {{ ' ' }}
                            <span class="font-medium">{{ schedules.to }}</span>
                            {{ ' ' }}
                            of
                            {{ ' ' }}
                            <span class="font-medium">{{ schedules.total }}</span>
                            {{ ' ' }}
                            results
                        </p>
                    </div>
                    <div>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                            <Link :href="!schedules.prev_page_url ? '#' : schedules.prev_page_url"
                                class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
                                as="button">
                            <ChevronLeftIcon class="h-4 w-4 u" aria-hidden="true" />
                            </Link>
                            <Link
                                v-for="link in schedules.links.filter((link, index) => index !== 0 && index !== schedules.links.length - 1)"
                                :key="link.label" :href="!link.url ? '#' : link.url"
                                class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 focus:z-20 focus:outline-offset-0"
                                :class="[link.label == schedules.current_page ? 'z-10 bg-indigo-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600' : 'ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-offset-0']"
                                :disabled="link.label == schedules.current_page" as="button" v-html="link.label">
                            </Link>
                            <Link :href="!schedules.next_page_url ? '#' : schedules.next_page_url"
                                class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
                                as="button">
                            <ChevronRightIcon class="h-4 w-4 u" aria-hidden="true" />
                            </Link>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <ModalUploadSchedule :schedule="selectedItem" :show="isUploadSchedule" @close="closeUploadSchedule"
            @exitUpdate="closeUploadSchedule" />
    </AuthenticatedLayout>
</template>