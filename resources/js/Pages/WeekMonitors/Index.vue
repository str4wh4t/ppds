<script setup>
import { ref, watch, computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link, usePage } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/20/solid';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import MultiselectBasic from '@/Components/MultiselectBasic.vue';
import SelectInput from '@/Components/SelectInputBasic.vue';
import moment from 'moment';

const week_monitors = usePage().props.week_monitors;

const startYear = 2024;
const endYear = 2030;

const yearList = Array.from({ length: endYear - startYear + 1 }, (_, i) => ({
    id: i + 1,
    name: String(startYear + i),
}));

const yearSelected = ref(usePage().props.filters.yearSelected);
const yearSelectedOpt = ref(yearList.find(year => year.name == yearSelected.value) || null);

const monthList = moment.months().map((month, index) => ({
    id: index,
    name: month
}));

const monthIndexSelected = ref(usePage().props.filters.monthIndexSelected);
const monthSelectedOpt = ref(monthList.find(month => month.id == monthIndexSelected.value) || null);

const categoryWorkLoadList = [
    { id: 1, name: '<71 jam' },
    { id: 2, name: '71 jam - 80 jam' },
    { id: 3, name: '81 jam - 88 jam' },
];

const categoryWorkloadSelected = ref(usePage().props.filters.categoryWorkloadSelected);
const categoryWorkloadSelectedOpt = ref(categoryWorkLoadList.find(categoryWorkLoad => categoryWorkLoad.id == categoryWorkloadSelected.value) || null);

const units = usePage().props.units;
const filters = ref({ search: usePage().props.filters.search ?? '', units: JSON.parse(usePage().props.filters.units) ?? [] });

const unitSelectedAsString = ref(JSON.stringify(filters.value.units));
const unitSelected = (selected) => {
    unitSelectedAsString.value = JSON.stringify(selected);
    router.get(route('week-monitors.index', { user: usePage().props.auth.user }), { search: filters.value.search, units: unitSelectedAsString.value, yearSelected: yearSelected.value || null, monthIndexSelected: monthIndexSelected.value + 1 || null, categoryWorkloadSelected: categoryWorkloadSelected.value || null}, { replace: true });
};

const searchPosts = () => {
    router.get(route('week-monitors.index', { user: usePage().props.auth.user }), { search: filters.value.search, units: unitSelectedAsString.value, yearSelected: yearSelected.value || null, monthIndexSelected: monthIndexSelected.value + 1 || null, categoryWorkloadSelected: categoryWorkloadSelected.value || null}, { replace: true });
};

const openCalendar = (weekGroupId, userId) => {
    router.get(route('activities.calendar', { user: usePage().props.auth.user }), { weekGroupId: weekGroupId, userId: userId }, { replace: true });
};

watch(
  () => yearSelectedOpt.value, // Amati perubahan `yearSelectedOpt.value`
  (newYear, oldYear) => {
    // if (newYear) {
      router.get(
        route("week-monitors.index", { user: usePage().props.auth.user }), // Gunakan nilai terbaru
        { 
          search: filters.value.search, 
          units: unitSelectedAsString.value, 
          yearSelected: newYear?.name || null,
          monthIndexSelected: monthIndexSelected.value + 1  || null,
          categoryWorkloadSelected: categoryWorkloadSelected.value || null
        },
        { replace: true }
      );
    // }
  },
);

watch(
  () => monthSelectedOpt.value, // Amati perubahan `monthSelectedOpt.value`
  (newMonth, oldMonth) => {
    // if (newMonth) {
      router.get(
        route("week-monitors.index", { user: usePage().props.auth.user }), // Gunakan nilai terbaru
        { 
          search: filters.value.search, 
          units: unitSelectedAsString.value, 
          yearSelected: yearSelected.value || null,
          monthIndexSelected: newMonth?.id + 1 || null,
          categoryWorkloadSelected: categoryWorkloadSelected.value || null
        },
        { replace: true }
      );
    // }
  },
);

watch(
  () => categoryWorkloadSelectedOpt.value, // Amati perubahan `monthSelectedOpt.value`
  (newCategoryWorkload, oldCategoryWorkload) => {
    // if (newCategoryWorkload) {
      router.get(
        route("week-monitors.index", { user: usePage().props.auth.user }), // Gunakan nilai terbaru
        { 
          search: filters.value.search, 
          units: unitSelectedAsString.value, 
          yearSelected: yearSelected.value || null,
          monthIndexSelected: monthIndexSelected.value + 1 || null,
          categoryWorkloadSelected: newCategoryWorkload?.id || null
        },
        { replace: true }
      );
    // }
  },
);
</script>

<template>

    <Head title="WorkMonitor List" />
    <AuthenticatedLayout>
        <template #header>
            Daftar Beban Kerja
        </template>
        <div class="sm:flex sm:items-center mt-4" v-if="!$hasRoles('student')">
            <div class="sm:flex-auto">
                <!-- <input type="text" name="name" id="name"
                    class="block w-full rounded-full border-0 px-4 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                    placeholder="" /> -->
                <div class="relative rounded-md shadow-sm">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <MagnifyingGlassIcon class="h-4 w-4 u text-gray-400" aria-hidden="true" />
                    </div>
                    <input v-model="filters.search" @keyup.enter="searchPosts" type="text" placeholder="Pencarian data"
                        class="block w-full rounded-full border-0 py-1.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                </div>
            </div>
            <div class="mt-4 sm:ml-5 sm:mt-0 sm:flex-initial w-48">
                <SelectInput id="yearSelected" class="block w-full" v-model="yearSelectedOpt" :options="yearList"
                    required autofocus autocomplete="yearSelected" placeholder="Select year" />
            </div>
            <div class="mt-4 sm:ml-5 sm:mt-0 sm:flex-initial w-48">
                <SelectInput id="monthSelected" class="block w-full" v-model="monthSelectedOpt" :options="monthList"
                    required autofocus autocomplete="monthSelected" placeholder="Select month" />
            </div>
            <div class="mt-4 sm:ml-5 sm:mt-0 sm:flex-initial w-48">
                <SelectInput id="categoryWorkloadSelected" class="block w-full" v-model="categoryWorkloadSelectedOpt" :options="categoryWorkLoadList"
                    required autofocus autocomplete="categoryWorkloadSelected" placeholder="Select category" />
            </div>
            <div class="mt-4 sm:ml-5 sm:mt-0 sm:flex-auto">
                <MultiselectBasic :options="units" v-model="filters.units" @optionSelected="unitSelected" />
            </div>
            
        </div>
        <div class="mt-4 ring-1 ring-gray-300 sm:mx-0 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
                <thead>
                    <tr>
                        <th scope="col" class="py-2 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                            Mhs
                        </th>
                        <th scope="col"
                            class="hidden px-3 py-2 text-left text-sm font-semibold text-gray-900 lg:table-cell">
                            Unit</th>
                        <th scope="col" class="px-3 py-2 text-sm font-semibold text-gray-900 lg:table-cell">
                            Tahun</th>
                        <th scope="col" class="px-3 py-2 text-sm font-semibold text-gray-900 lg:table-cell">
                            Bulan</th>
                        <th scope="col" class="px-3 py-2 text-sm font-semibold text-gray-900 lg:table-cell">
                            Minggu</th>
                        <th scope="col" class="px-3 py-2 text-sm font-semibold text-gray-900 lg:table-cell">
                            Beban Kerja</th>
                        <th scope="col" class="relative py-1 pl-3 pr-4 sm:pr-6">
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(week_monitor, index) in week_monitors.data" :key="week_monitor.id"
                        :class="[week_monitor.workload_hours > 80 ? 'bg-red-200 hover:bg-red-300' : week_monitor.workload_hours > 70 ? 'bg-yellow-100 hover:bg-yellow-200' : '']">
                        <td
                            :class="[index === 0 ? '' : 'border-t border-transparent', 'relative py-1 pl-4 pr-3 text-sm sm:pl-6']">
                            <div class="font-medium text-gray-900">
                                {{ week_monitor.user.fullname }}
                            </div>
                            <div class="mt-1 flex flex-col text-gray-500 sm:block lg:hidden">
                                <span>{{ week_monitor.user.student_unit.name }}</span>
                            </div>
                            <div v-if="index !== 0" class="absolute -top-px left-6 right-0 h-px bg-gray-200" />
                        </td>
                        <td
                            :class="[index === 0 ? '' : 'border-t border-gray-200', 'hidden px-3 py-2 text-sm text-gray-500 lg:table-cell']">
                            {{ week_monitor.user.student_unit.name }}</td>
                        <td
                            :class="[index === 0 ? '' : 'border-t border-gray-200', 'px-3 py-2 text-sm text-center text-gray-500 lg:table-cell']">
                            {{ week_monitor.year }}</td>
                        <td
                            :class="[index === 0 ? '' : 'border-t border-gray-200', 'px-3 py-2 text-sm text-center text-gray-500 lg:table-cell']">
                            {{ moment().month(week_monitor.month - 1).format("MMMM") }}</td>
                        <td
                            :class="[index === 0 ? '' : 'border-t border-gray-200', 'px-3 py-2 text-sm text-center text-gray-500 lg:table-cell']">
                            {{ week_monitor.week_month }}</td>
                        <td
                            :class="[index === 0 ? '' : 'border-t border-gray-200', 'px-3 py-2 text-sm text-center text-gray-500 lg:table-cell']">
                            {{ week_monitor.workload }}
                        </td>
                        <td
                            :class="[index === 0 ? '' : 'border-t border-transparent', 'relative py-1 pl-3 pr-4 text-center text-sm font-medium sm:pr-6']">
                            <button type="button"
                                @click="openCalendar(week_monitor.week_group_id, week_monitor.user_id)"
                                class="inline-flex items-center rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-white">
                                Edit
                            </button>
                            <div v-if="index !== 0" class="absolute -top-px left-0 right-6 h-px bg-gray-200" />
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6">
                <div class="flex flex-1 justify-between sm:hidden">
                    <Link :href="!week_monitors.prev_page_url ? '#' : week_monitors.prev_page_url"
                        class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        as="button">
                    Previous
                    </Link>

                    <Link :href="!week_monitors.next_page_url ? '#' : week_monitors.next_page_url"
                        class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        as="button">
                    Next
                    </Link>
                </div>
                <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing
                            {{ ' ' }}
                            <span class="font-medium">{{ week_monitors.from }}</span>
                            {{ ' ' }}
                            to
                            {{ ' ' }}
                            <span class="font-medium">{{ week_monitors.to }}</span>
                            {{ ' ' }}
                            of
                            {{ ' ' }}
                            <span class="font-medium">{{ week_monitors.total }}</span>
                            {{ ' ' }}
                            results
                        </p>
                    </div>
                    <div>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                            <Link :href="!week_monitors.prev_page_url ? '#' : week_monitors.prev_page_url"
                                class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
                                as="button">
                            <ChevronLeftIcon class="h-4 w-4 u" aria-hidden="true" />
                            </Link>
                            <Link
                                v-for="link in week_monitors.links.filter((link, index) => index !== 0 && index !== week_monitors.links.length - 1)"
                                :key="link.label" :href="!link.url ? '#' : link.url"
                                class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 focus:z-20 focus:outline-offset-0"
                                :class="[link.label == week_monitors.current_page ? 'z-10 bg-indigo-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600' : 'ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-offset-0']"
                                :disabled="link.label == week_monitors.current_page" as="button" v-html="link.label">
                            </Link>
                            <Link :href="!week_monitors.next_page_url ? '#' : week_monitors.next_page_url"
                                class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
                                as="button">
                            <ChevronRightIcon class="h-4 w-4 u" aria-hidden="true" />
                            </Link>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>