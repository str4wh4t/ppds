<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, Head, usePage, router } from '@inertiajs/vue3';
import { PaperClipIcon, UserCircleIcon, ChevronLeftIcon, ChevronRightIcon, MagnifyingGlassIcon } from '@heroicons/vue/20/solid';
import { PlusCircleIcon } from '@heroicons/vue/24/outline';
import MultiselectBasic from '@/Components/MultiselectBasic.vue';
import SelectInput from '@/Components/SelectInputBasic.vue';
import CreateButton from '@/Components/CreateButton.vue';
import moment from 'moment';
import { ref, watch } from 'vue';

// Mengambil props dari server (Laravel Inertia)
const units = usePage().props.units;

const filters = ref({ search: usePage().props.filters.search ?? '', units: JSON.parse(usePage().props.filters.units) ?? [] });

const stases = usePage().props.stases;
const user_stase_counts = usePage().props.user_stase_counts;

const getStaseInfo = (stases, staseId) => {
    // Misalkan user memiliki array unit_stases terkait stase, kita cari informasi stase di dalamnya
    const stase = stases.find(stase => stase.stase_id === staseId) ?? null;
    return stase?.count ?? 0; // Ganti some_related_data dengan informasi yang relevan
};

const unitSelectedAsString = ref(JSON.stringify(filters.value.units));
const unitSelected = (selected) => {
    unitSelectedAsString.value = JSON.stringify(selected);
    router.get(route('activities.report', { user: usePage().props.auth.user }), { search: filters.value.search, units: unitSelectedAsString.value }, { replace: true });
};

const searchPosts = () => {
    router.get(route('activities.report', { user: usePage().props.auth.user }), { search: filters.value.search, units: unitSelectedAsString.value }, { replace: true });
};

// const monthList = moment.months().map((month, index) => ({
//     id: index,
//     name: month
// }));

// const monthIndexSelected = ref(usePage().props.filters.monthIndexSelected);
// console.log('1', monthIndexSelected.value);
// console.log('3', monthList.value);
// const monthSelectedOpt = ref(monthList.find(month => month.id == monthIndexSelected.value) || null);
// console.log('2', monthSelectedOpt.value);
// console.log(monthSelectedOpt.value);
/*
watch(
  () => monthSelectedOpt.value, // Amati perubahan `monthSelectedOpt.value`
  (newMonth, oldMonth) => {
    // if (newMonth) {
      router.get(
        route("activities.report", { user: usePage().props.auth.user }), // Gunakan nilai terbaru
        { 
          search: filters.value.search, 
          units: unitSelectedAsString.value, 
          monthIndexSelected: newMonth?.id || null
        },
        { replace: true }
      );
    // }
  },
);
*/
</script>

<template>

    <Head title="Report" />
    <AuthenticatedLayout>
        <template #header>
            Report
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
            <div class="mt-4 sm:ml-5 sm:mt-0 sm:flex-auto w-24">
                <MultiselectBasic :options="units" v-model="filters.units" @optionSelected="unitSelected" />
            </div>
            <!--
            <div class="mt-4 sm:ml-5 sm:mt-0 sm:flex-auto w-24">
                <SelectInput id="monthSelected" v-model="monthSelectedOpt" :options="monthList"
                        required autofocus autocomplete="monthSelected" placeholder="Select month" />
            </div>
            -->
        </div>
        <div class="mt-4 ring-1 ring-gray-300 sm:mx-0 sm:rounded-lg">
            <div class="overflow-x-auto w-full">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead>
                        <tr>
                            <th scope="col"
                                class="py-2 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                                Mahasiswa</th>
                            <th scope="col"
                                class="hidden px-3 py-2 text-left text-sm font-semibold text-gray-900 lg:table-cell">
                                NIM
                            </th>
                            <!--
                            <th scope="col"
                                class="hidden px-3 py-2 text-left text-sm font-semibold text-gray-900 lg:table-cell">
                                Bulan
                            </th>
                            -->
                            <!-- Loop untuk membuat header kolom berdasarkan stases -->
                            <th v-for="stase in stases" :key="stase.id" scope="col"
                                class="px-3 py-2 text-sm font-semibold text-gray-900 lg:table-cell">
                                <div class="whitespace-nowrap">
                                    {{ stase.name }}
                                </div>
                                <!--
                                <div class="text-gray-500 text-xs">
                                    {{ stase.location }}
                                </div>
                                -->
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loop untuk membuat row berdasarkan user_stase_counts -->
                        <tr v-if="$hasItems(user_stase_counts)" v-for="(user_stase_count, index) in user_stase_counts"
                            :key="user_stase_count.user.user_id">
                            <td
                                :class="[index === 0 ? '' : 'border-t border-transparent', 'relative py-1 pl-4 pr-3 text-sm sm:pl-6']">
                                <div class="font-medium text-gray-900 whitespace-nowrap">
                                    {{ user_stase_count.user.fullname }}
                                </div>
                                <div class="mt-1 flex flex-col text-gray-500 sm:block lg:hidden">
                                    <span class="block">{{ user_stase_count.user.identity }}</span>
                                </div>
                                <div v-if="index !== 0" class="absolute -top-px left-6 right-0 h-px bg-gray-200" />
                            </td>
                            <td
                                :class="[index === 0 ? '' : 'border-t border-gray-200', 'hidden px-3 py-2 text-sm text-gray-500 lg:table-cell']">
                                {{ user_stase_count.user.identity }}</td>
                            <!--
                            <td
                                :class="[index === 0 ? '' : 'border-t border-gray-200', 'hidden px-3 py-2 text-sm text-gray-500 lg:table-cell']">
                                {{ '' }}</td>
                            -->
                            <!-- Loop untuk mengisi kolom berdasarkan stases -->
                            <td v-for="(stase, staseIndex) in stases" :key="stase.id"
                                :class="[index === 0 ? '' : staseIndex === stases.length - 1 ? 'border-t border-transparent relative' : 'border-t border-gray-200 px-3 py-2 ', 'text-sm text-center text-gray-500 lg:table-cell']">
                                <!-- Logika untuk menampilkan data terkait stase di user_stase_counts -->
                                {{ getStaseInfo(user_stase_count.stases, stase.id) }}
                                <div v-if="index !== 0 && staseIndex === stases.length - 1"
                                    class="absolute -top-px left-0 right-6 h-px bg-gray-200" />
                            </td>
                        </tr>
                        <tr v-else>
                            <td :colspan="2 + stases.length" class="h-10 text-center font-bold text-indigo-600">Empty
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
