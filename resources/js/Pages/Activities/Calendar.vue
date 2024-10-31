<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Link, usePage } from '@inertiajs/vue3';
import ModalEntryActivity from './Modals/Calendar/EntryActivity.vue';
import CreateButton from '@/Components/CreateButton.vue';
import moment from 'moment';
// import { generateDays } from '../helpers/calendar.js';
import { ref, onMounted } from 'vue';

import {
    ChevronDownIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ClockIcon,
    EllipsisHorizontalIcon,
} from '@heroicons/vue/20/solid'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { CalendarDaysIcon } from '@heroicons/vue/24/outline';

const days = ref([]);
const thisMonth = new Date().getMonth();
const pointerMonth = ref(thisMonth);
const labelMonth = ref(moment().format('MMMM'));
const labelYear = ref(moment().format('YYYY'));
const isLoading = ref(true);
const selectedDay = ref({});
// days.value = generateDays(labelYear.value, pointerMonth.value);

const generateDays = async (year, month, callback = null) => {
    try {
        isLoading.value = true;
        await axios
            .post(route('calendar.generatedays', { user: usePage().props.auth.user }), {
                year: labelYear.value,
                month: pointerMonth.value,
            })
            .then(response => {
                days.value = response.data.days;
                if (callback) {
                    callback();
                }
            })
            .catch(error => {
                console.error('Error fetching users:', error);
            });
    } catch (error) {
        console.error("Error fetching data:", error);
    } finally {
        //
        isLoading.value = false;
    }
}

onMounted(async () => {
    await generateDays(labelYear.value, pointerMonth.value);
});

const goPrevMonth = async () => {
    pointerMonth.value = pointerMonth.value - 1;
    labelMonth.value = moment().month(pointerMonth.value).format('MMMM');
    // days.value = generateDays(labelYear.value, pointerMonth.value);
    await generateDays(labelYear.value, pointerMonth.value);

    if (pointerMonth.value < 0) {
        labelYear.value = +labelYear.value - 1;
        pointerMonth.value = 11;
    }
}

const goNextMonth = async () => {
    pointerMonth.value = pointerMonth.value + 1;
    labelMonth.value = moment().month(pointerMonth.value).format('MMMM');
    // days.value = generateDays(labelYear.value, pointerMonth.value);
    await generateDays(labelYear.value, pointerMonth.value);
    if (pointerMonth.value > 11) {
        labelYear.value = +labelYear.value + 1;
        pointerMonth.value = 0;
    }
}

const goThisMonth = async () => {
    pointerMonth.value = thisMonth;
    labelYear.value = moment().format('YYYY');
    // days.value = generateDays(labelYear.value, pointerMonth.value);
    await generateDays(labelYear.value, pointerMonth.value);
}

const isCreate = ref(false);
const userActivities = ref([]);

const chooseDate = (date) => {
    const filteredActivities = usePage().props.activities.filter(activity =>
        activity.start_date.startsWith(date)
    );
    userActivities.value = filteredActivities;
    userActivities.value.sort((a, b) => new Date(a.start_date) - new Date(b.start_date));
    selectedDay.value = days.value.find(item => item.date === date);
    isCreate.value = true;
    // axios
    //     .post(route('activities.getuseractivities', { user: usePage().props.auth.user }), {
    //         date: date,
    //     })
    //     .then(response => {
    //         userActivities.value = response.data.activities;
    //         selectedDay.value = days.value.find(item => item.date === date);
    //         isCreate.value = true;
    //     })
    //     .catch(error => {
    //         console.error('Error fetching users:', error);
    //     });
}

const replaceProps = async (props, selected = null) => {
    await generateDays(labelYear.value, pointerMonth.value, () => {
        selectedDay.value = days.value.find(item => item.date === selected.date);
        const filteredActivities = props.activities.filter(activity =>
            activity.start_date.startsWith(selectedDay.value.date)
        );
        userActivities.value = filteredActivities;
    });
};

</script>

<template>

    <Head title="Calendar" />
    <AuthenticatedLayout>
        <template #header>
            Kalender
        </template>
        <div v-if="!isLoading" class="lg:flex lg:h-full lg:flex-col">
            <header class="flex items-center justify-between border-b border-gray-200 py-4 lg:flex-none">
                <Link :href="route('activities.schedule', { user: usePage().props.auth.user })"
                    class="hidden sm:inline-flex items-center gap-x-1.5 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                    type="button">
                <CalendarDaysIcon class="-ml-0.5 h-5 w-5" aria-hidden="true" /> Lihat Jadwal
                </Link>
                <h1 class="text-base font-semibold leading-6 text-gray-900">
                    <span>{{ labelMonth }} {{ labelYear }}</span>
                </h1>
                <div class="flex items-center">
                    <div class="relative flex items-center rounded-md bg-white shadow-sm md:items-stretch ml-1">
                        <button @click="goPrevMonth()" type="button"
                            class="flex h-9 w-12 items-center justify-center rounded-l-md border-y border-l border-gray-300 pr-1 text-gray-400 hover:text-indigo-500 focus:relative md:w-9 md:pr-0 hover:bg-indigo-50">
                            <span class="sr-only">Bulan lalu</span>
                            <ChevronLeftIcon class="h-5 w-5" aria-hidden="true" />
                        </button>
                        <button @click="goThisMonth()" type="button"
                            class="hidden border-y border-gray-300 px-3.5 text-sm font-semibold text-gray-900 hover:bg-indigo-50 hover:text-indigo-500 focus:relative md:block">Bulan
                            ini</button>
                        <span class="relative -mx-px h-5 w-px bg-gray-300 md:hidden" />
                        <button @click="goNextMonth()" type="button"
                            class="flex h-9 w-12 items-center justify-center rounded-r-md border-y border-r border-gray-300 pl-1 text-gray-400 hover:text-indigo-500 focus:relative md:w-9 md:pl-0 hover:bg-indigo-50">
                            <span class="sr-only">Bulan depan</span>
                            <ChevronRightIcon class="h-5 w-5" aria-hidden="true" />
                        </button>
                    </div>
                    <!-- <Menu as="div" class="relative ml-6 md:hidden">
                        <MenuButton
                            class="-mx-2 flex items-center rounded-full border border-transparent p-2 text-gray-400 hover:text-gray-500">
                            <span class="sr-only">Open menu</span>
                            <EllipsisHorizontalIcon class="h-5 w-5" aria-hidden="true" />
                        </MenuButton>

                        <transition enter-active-class="transition ease-out duration-100"
                            enter-from-class="transform opacity-0 scale-95"
                            enter-to-class="transform opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-75"
                            leave-from-class="transform opacity-100 scale-100"
                            leave-to-class="transform opacity-0 scale-95">
                            <MenuItems
                                class="absolute right-0 z-10 mt-3 w-36 origin-top-right divide-y divide-gray-100 overflow-hidden rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                                <div class="py-1">
                                    <MenuItem v-slot="{ active }">
                                    <a href="#"
                                        :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block px-4 py-2 text-sm']">Create
                                        event</a>
                                    </MenuItem>
                                </div>
                                <div class="py-1">
                                    <MenuItem v-slot="{ active }">
                                    <a href="#"
                                        :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block px-4 py-2 text-sm']">Go
                                        to hari ini</a>
                                    </MenuItem>
                                </div>
                                <div class="py-1">
                                    <MenuItem v-slot="{ active }">
                                    <a href="#"
                                        :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block px-4 py-2 text-sm']">Day
                                        view</a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                    <a href="#"
                                        :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block px-4 py-2 text-sm']">Week
                                        view</a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                    <a href="#"
                                        :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block px-4 py-2 text-sm']">Month
                                        view</a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                    <a href="#"
                                        :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block px-4 py-2 text-sm']">Year
                                        view</a>
                                    </MenuItem>
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu> -->
                </div>
            </header>
            <div class="shadow ring-1 ring-black ring-opacity-5 lg:flex lg:flex-auto lg:flex-col">
                <div
                    class="grid grid-cols-7 gap-px border-b border-gray-300 bg-gray-200 text-center text-xs font-semibold leading-6 text-gray-700 lg:flex-none">
                    <div class="bg-white py-2">M<span class="sr-only sm:not-sr-only">on</span></div>
                    <div class="bg-white py-2">T<span class="sr-only sm:not-sr-only">ue</span></div>
                    <div class="bg-white py-2">W<span class="sr-only sm:not-sr-only">ed</span></div>
                    <div class="bg-white py-2">T<span class="sr-only sm:not-sr-only">hu</span></div>
                    <div class="bg-white py-2">F<span class="sr-only sm:not-sr-only">ri</span></div>
                    <div class="bg-white py-2">S<span class="sr-only sm:not-sr-only">at</span></div>
                    <div class="bg-white py-2">S<span class="sr-only sm:not-sr-only">un</span></div>
                </div>
                <div class="flex bg-gray-300 text-xs leading-6 text-gray-700 lg:flex-auto">
                    <div class="hidden w-full lg:grid lg:grid-cols-7 lg:grid-rows-6 lg:gap-px">
                        <!-- <div @click="chooseDate(day.date)" v-for="day in days" :key="day.date"
                            :class="[day.isCurrentMonth ? (day.isDanger ? 'bg-red-200' : (day.isWarning ? 'bg-yellow-100' : 'bg-white')) : 'bg-gray-50 text-gray-500', 'relative px-3 py-1 h-32 cursor-pointer']"> -->
                        <div @click="day.isCurrentMonth ? chooseDate(day.date) : '#'" v-for="day in days"
                            :key="day.date"
                            :class="[day.isCurrentMonth ? 'font-semibold ' + (day.isDanger ? 'bg-red-200 hover:bg-red-300' : (day.isWarning ? 'bg-yellow-100 hover:bg-yellow-200' : 'hover:bg-gray-100 bg-white')) : 'bg-gray-200 text-gray-500', 'relative px-3 py-1 h-32 cursor-pointer']">
                            <!-- <time :datetime="day.date"
                                :class="day.isToday ? 'flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 font-semibold text-white' : undefined">{{
                                    day.date.split('-').pop().replace(/^0/, '') }}</time> -->
                            <!-- <div class="flex">
                                <time :datetime="day.date"
                                    :class="day.isToday ? 'flex-none h-6 w-6 text-center items-center justify-center rounded-full bg-indigo-600 font-semibold text-white' : ''">{{
                                        day.date.split('\/')[0] }}</time><span
                                    class="flex-auto text-right text-indigo-600 font-bold"
                                    v-if="day.isToday">hari ini</span>
                            </div> -->
                            <div class="flex">
                                <time :datetime="day.date"
                                    :class="[day.isToday ? 'text-indigo-600 font-bold' : '', 'flex-none text-center']">{{
                                        day.date.split('-').pop().replace(/^0/, '') }}</time><span
                                    class="flex-auto text-right font-bold text-indigo-600" v-if="day.isToday">hari
                                    ini</span>
                            </div>
                            <ol v-if="day.events.length > 0">
                                <li class="" v-for="event in day.events.slice(0, 3)" :key="event.id">
                                    <!-- <a :href="event.href" class="group flex">
                                        <p
                                            class="flex-auto truncate font-medium text-gray-900 group-hover:text-indigo-600">
                                            {{ event.name }}
                                        </p>
                                        <time :datetime="event.datetime"
                                            class="ml-3 hidden flex-none text-gray-500 group-hover:text-indigo-600 xl:block">{{
                                                event.time }}</time>
                                    </a> -->
                                    <span class="group flex">
                                        <p class="flex-auto truncate font-medium text-gray-900">
                                            {{ event.name }}
                                        </p>
                                        <time :datetime="event.start_date"
                                            class="ml-3 hidden flex-none text-gray-500 lg:block">{{
                                                $formatDate({ date: event.start_date, formatOutput: 'HH:mm' }) }}</time>
                                    </span>
                                </li>
                                <li v-if="day.events.length > 3" class="text-gray-500">+ {{ day.events.length - 3 }}
                                    more
                                </li>
                            </ol>
                        </div>
                    </div>
                    <div class="isolate grid w-full grid-cols-7 grid-rows-6 gap-px lg:hidden">
                        <button @click="day.isCurrentMonth ? chooseDate(day.date) : '#'" v-for="day in days"
                            :key="day.date" type="button"
                            :class="[day.isCurrentMonth ? 'font-semibold ' + (day.isDanger ? 'bg-red-200 hover:bg-red-300' : (day.isWarning ? 'bg-yellow-100 hover:bg-yellow-200' : 'hover:bg-gray-100 bg-white')) : 'bg-gray-200 text-gray-500', 'flex h-14 flex-col px-1 py-1 focus:z-10']">
                            <!-- <time :datetime="day.date"
                                :class="[day.isSelected && 'flex h-6 w-6 items-center justify-center rounded-full', day.isSelected && day.isToday && 'bg-indigo-600', day.isSelected && !day.isToday && 'bg-gray-900', 'ml-auto']">{{
                                    day.date.split('\/')[0] }}</time> -->
                            <time :datetime="day.date" :class="[day.isToday && 'text-indigo-600', 'ml-auto']">{{
                                day.date.split('-').pop().replace(/^0/, '') }}</time>
                            <span class="sr-only">{{ day.events.length }} events</span>
                            <span v-if="day.events.length > 0" class="-mx-0.5 mt-auto flex flex-wrap-reverse">
                                <span v-for="event in day.events" :key="event.id"
                                    class="mx-0.5 mb-1 h-1.5 w-1.5 rounded-full bg-gray-400" />
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    <ModalEntryActivity :selectedDay="selectedDay" :activities="userActivities" :show="isCreate"
        @close="isCreate = false" @updateProps="replaceProps" />
</template>