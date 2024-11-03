<script setup>
import { ref, watch, computed } from 'vue'
import {
    Dialog,
    DialogPanel,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    TransitionChild,
    TransitionRoot,
    Disclosure, DisclosureButton, DisclosurePanel
} from '@headlessui/vue'
import {
    Bars3Icon,
    CalendarIcon,
    ChartPieIcon,
    Cog6ToothIcon,
    DocumentDuplicateIcon,
    FolderIcon,
    HomeIcon,
    QueueListIcon,
    UsersIcon,
    XMarkIcon,
    ChevronRightIcon,
    UserGroupIcon,
    ClipboardDocumentListIcon,
    MinusIcon,
    ChatBubbleBottomCenterIcon,
    SpeakerWaveIcon,
    ExclamationCircleIcon,
    PresentationChartBarIcon
} from '@heroicons/vue/24/outline'
import { ChevronDownIcon, MagnifyingGlassIcon, BellIcon } from '@heroicons/vue/20/solid'
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ModalGlobal from '@/Components/ModalGlobal.vue';
import { useModalStore } from '@/stores/modalStore';
import { Link, usePage } from '@inertiajs/vue3';
import LinkNotif from './Notification/LinkNotif.vue';

const defaultnav = [
    { name: 'Dashboard', href: route('dashboard'), icon: HomeIcon, current: route().current('dashboard'), method: 'get' },
];

const navigation = [
    { name: 'Logbook', href: route('activities.index', { user: usePage().props.auth.user }), icon: FolderIcon, current: route().current('activities.index'), method: 'get' },
    { name: 'Kalender', href: route('activities.calendar', { user: usePage().props.auth.user }), icon: CalendarIcon, current: route().current('activities.calendar'), method: 'get' },
    { name: 'Beban Kerja', href: route('week-monitors.index', { user: usePage().props.auth.user }), icon: PresentationChartBarIcon, current: route().current('week-monitors.index'), method: 'get' },
    { name: 'Konsultasi', href: route('consults.index', { user: usePage().props.auth.user }), icon: ChatBubbleBottomCenterIcon, current: route().current('consults.index'), method: 'get' },
    { name: 'Portofolio', href: route('portofolios.index', { user: usePage().props.auth.user }), icon: DocumentDuplicateIcon, current: route().current('portofolios.index'), method: 'get' },
    { name: 'Report', href: route('activities.report', { user: usePage().props.auth.user }), icon: ChartPieIcon, current: route().current('activities.report'), method: 'get' },
    { name: 'Laporkan', href: route('speaks.index', { user: usePage().props.auth.user }), icon: ExclamationCircleIcon, current: route().current('speaks.index'), method: 'get' },
];

const adminNavigation = [
    { name: 'Logbook', href: route('activities.index', { user: usePage().props.auth.user }), icon: FolderIcon, current: route().current('activities.index'), method: 'get' },
    // { name: 'Kalender', href: route('activities.calendar', { user: usePage().props.auth.user }), icon: CalendarIcon, current: route().current('activities.calendar'), method: 'get' },
    { name: 'Beban Kerja', href: route('week-monitors.index', { user: usePage().props.auth.user }), icon: PresentationChartBarIcon, current: route().current('week-monitors.index') || route().current('activities.calendar'), method: 'get' },
    { name: 'Konsultasi', href: route('consults.student-list'), icon: ChatBubbleBottomCenterIcon, current: route().current('consults.student-list') || route().current('consults.index'), method: 'get' },
    { name: 'Portofolio', href: route('portofolios.index', { user: usePage().props.auth.user }), icon: DocumentDuplicateIcon, current: route().current('portofolios.index'), method: 'get' },
    { name: 'Report', href: route('activities.report', { user: usePage().props.auth.user }), icon: ChartPieIcon, current: route().current('activities.report'), method: 'get' },
    { name: 'Laporkan', href: route('speaks.student-list'), icon: ExclamationCircleIcon, current: route().current('speaks.student-list') || route().current('speaks.index'), method: 'get' },
];

const masterNavigation = [
    { name: 'Units', href: route('units.index'), icon: QueueListIcon, current: route().current('units.index') },
    { name: 'Stase', href: route('stases.index'), icon: ClipboardDocumentListIcon, current: route().current('stases.index') },
    {
        name: 'Users',
        icon: UsersIcon,
        current: route().current('kaprodis.index') || route().current('dosens.index'),
        href: '#',
        children: [
            { name: 'Dosen', href: route('dosens.index'), icon: MinusIcon, current: route().current('dosens.index'), method: 'get' },
            { name: 'Kaprodi', href: route('kaprodis.index'), icon: MinusIcon, current: route().current('kaprodis.index'), method: 'get' },
        ],
    },
    { name: 'Students', href: route('students.index'), icon: UserGroupIcon, current: route().current('students.index'), method: 'get' },
];

const accessControlNavigation = [
    { id: 1, name: 'Users', href: route('users.index'), initial: 'U', current: route().current('users.index'), method: 'get' },
    { id: 2, name: 'Roles', href: route('roles.index'), initial: 'R', current: route().current('roles.index'), method: 'get' },
    { id: 3, name: 'Permisions', href: route('permissions.index'), initial: 'P', current: route().current('permissions.index'), method: 'get' },
];

const userNavigation = [
    { name: 'Your profile', href: route('profile.edit'), method: 'get' },
    { name: 'Sign out', href: route('logout'), method: 'post' },
];

// const availRoles = usePage().props.auth.user.roles;

const sidebarOpen = ref(false);

const isOpenDisclosure = (item) => {
    return item.children && item.children.some(subItem => subItem.current);
}

const modalStore = useModalStore();
const flashmsg = computed(() => usePage().props.flashmsg);

watch(
    () => flashmsg.value.ko,
    (newValue, oldValue) => {
        if (newValue !== null) {
            modalStore.openModal('Error', 'Terjadi kesalahan saat mengirim data.');
        }
    }, { immediate: true }
);

</script>
<template>
    <ModalGlobal />
    <TransitionRoot as="template" :show="sidebarOpen">
        <Dialog class="relative z-50 lg:hidden" @close="sidebarOpen = false">
            <TransitionChild as="template" enter="transition-opacity ease-linear duration-300" enter-from="opacity-0"
                enter-to="opacity-100" leave="transition-opacity ease-linear duration-300" leave-from="opacity-100"
                leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-900/80" />
            </TransitionChild>

            <div class="fixed inset-0 flex">
                <TransitionChild as="template" enter="transition ease-in-out duration-300 transform"
                    enter-from="-translate-x-full" enter-to="translate-x-0"
                    leave="transition ease-in-out duration-300 transform" leave-from="translate-x-0"
                    leave-to="-translate-x-full">
                    <DialogPanel class="relative mr-16 flex w-full max-w-xs flex-1">
                        <TransitionChild as="template" enter="ease-in-out duration-300" enter-from="opacity-0"
                            enter-to="opacity-100" leave="ease-in-out duration-300" leave-from="opacity-100"
                            leave-to="opacity-0">
                            <div class="absolute left-full top-0 flex w-16 justify-center pt-5">
                                <button type="button" class="-m-2.5 p-2.5" @click="sidebarOpen = false">
                                    <span class="sr-only">Close sidebar</span>
                                    <XMarkIcon class="h-6 w-6 text-white" aria-hidden="true" />
                                </button>
                            </div>
                        </TransitionChild>
                        <!-- Sidebar component, swap this element with another sidebar if you like -->
                        <div class="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 bg-white px-6">
                            <div class="flex h-16 shrink-0 items-center">
                                <!-- <img class="h-8 w-auto"
                                        src="https://tailwindui.com/plus/img/logos/mark.svg?color=indigo&shade=600"
                                        alt="Your Company" /> -->
                                <ApplicationLogo />
                            </div>
                            <nav class="flex flex-1 flex-col">
                                <ul role="list" class="flex flex-1 flex-col gap-y-3">
                                    <li>
                                        <ul role="list" class="-mx-2">
                                            <li v-for="item in defaultnav" :key="item.name">
                                                <!-- <a :href="item.href"
                                                        :class="[item.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-600', 'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6']">
                                                        <component :is="item.icon"
                                                            :class="[item.current ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600', 'h-6 w-6 shrink-0']"
                                                            aria-hidden="true" />
                                                        {{ item.name }}
                                                    </a> -->
                                                <Link :href="item.href"
                                                    :class="[item.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-600', 'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6']"
                                                    :method="item.method" as="button">
                                                <component :is="item.icon"
                                                    :class="[item.current ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600', 'h-6 w-6 shrink-0']"
                                                    aria-hidden="true" />
                                                {{ item.name }}
                                                </Link>
                                            </li>
                                            <li v-if="$hasRoles('student')" v-for="item in navigation" :key="item.name">
                                                <!-- <a :href="item.href"
                                                        :class="[item.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-600', 'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6']">
                                                        <component :is="item.icon"
                                                            :class="[item.current ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600', 'h-6 w-6 shrink-0']"
                                                            aria-hidden="true" />
                                                        {{ item.name }}
                                                    </a> -->
                                                <Link :href="item.href"
                                                    :class="[item.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-600', 'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6']"
                                                    :method="item.method" as="button">
                                                <component :is="item.icon"
                                                    :class="[item.current ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600', 'h-6 w-6 shrink-0']"
                                                    aria-hidden="true" />
                                                {{ item.name }}
                                                </Link>
                                            </li>
                                            <li v-if="!$hasRoles('student')" v-for="item in adminNavigation"
                                                :key="item.name">
                                                <!-- <a :href="item.href"
                                                        :class="[item.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-600', 'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6']">
                                                        <component :is="item.icon"
                                                            :class="[item.current ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600', 'h-6 w-6 shrink-0']"
                                                            aria-hidden="true" />
                                                        {{ item.name }}
                                                    </a> -->
                                                <Link :href="item.href"
                                                    :class="[item.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-600', 'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6']"
                                                    :method="item.method" as="button">
                                                <component :is="item.icon"
                                                    :class="[item.current ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600', 'h-6 w-6 shrink-0']"
                                                    aria-hidden="true" />
                                                {{ item.name }}
                                                </Link>
                                            </li>
                                        </ul>
                                    </li>
                                    <div v-if="$hasRoles(['admin_prodi', 'admin_fakultas'])">
                                        <div class="text-xs font-semibold leading-6 text-gray-400">Master Data</div>
                                        <li v-for="item in masterNavigation" :key="item.name" class="-mx-2">
                                            <Link v-if="!item.children" :href="item.href"
                                                :class="[item.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-600', 'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6']"
                                                :method="item.method" as="button">
                                            <component :is="item.icon"
                                                :class="[item.current ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600', 'h-6 w-6 shrink-0']"
                                                aria-hidden="true" />
                                            {{ item.name }}
                                            </Link>
                                            <Disclosure as="div" v-else :defaultOpen="isOpenDisclosure(item)"
                                                v-slot="{ open }">
                                                <DisclosureButton
                                                    :class="[item.current ? 'bg-gray-50 text-indigo-600' : 'hover:bg-gray-50 hover:text-indigo-600', 'group flex w-full items-center gap-x-3 rounded-md p-2 text-left text-sm font-semibold leading-6 text-gray-700']">
                                                    <component :is="item.icon"
                                                        :class="[item.current ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600', 'h-6 w-6 shrink-0']"
                                                        aria-hidden=" true" />
                                                    {{ item.name }}
                                                    <ChevronRightIcon
                                                        :class="[open ? 'rotate-90 text-gray-500' : 'text-gray-400', 'ml-auto h-5 w-5 shrink-0']"
                                                        aria-hidden="true" />
                                                </DisclosureButton>
                                                <DisclosurePanel as="ul" class="mt-1 px-2">
                                        <li v-for="subItem in item.children" :key="subItem.name">
                                            <!-- 44px -->
                                            <Link :href="subItem.href"
                                                :class="[subItem.current ? 'bg-gray-50 text-indigo-600' : 'hover:bg-gray-50 hover:text-indigo-600', 'flex w-full rounded-md gap-x-3 py-2 pl-9 pr-2 text-sm leading-6 text-gray-700']"
                                                :method="subItem.method" as="button">
                                            <component :is="subItem.icon"
                                                :class="[subItem.current ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600', 'h-6 w-6 shrink-0']"
                                                aria-hidden="true" />
                                            {{ subItem.name }}
                                            </Link>
                                        </li>
                                        </DisclosurePanel>
                                        </Disclosure>
                                        </li>
                                    </div>
                                    <div v-if="$hasPerms('access-control')">
                                        <div class="text-xs font-semibold leading-6 text-gray-400">Access
                                            Control
                                        </div>
                                        <li>
                                            <ul role="list" class="-mx-2 mt-2">
                                                <li v-for="item in accessControlNavigation" :key="item.name">
                                                    <Link :href="item.href"
                                                        :class="[item.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-600', 'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6']"
                                                        :method="item.method" as="button">
                                                    <span
                                                        :class="[item.current ? 'border-indigo-600 text-indigo-600' : 'border-gray-200 text-gray-400 group-hover:border-indigo-600 group-hover:text-indigo-600', 'flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border bg-white text-[0.625rem] font-medium']">{{
                                                            item.initial }}</span>
                                                    <span class="truncate">{{ item.name }}</span>
                                                    </Link>
                                                </li>
                                            </ul>
                                        </li>
                                    </div>
                                    <li class="mt-auto">
                                        <a href="#"
                                            class="group -mx-2 flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6 text-gray-700 hover:bg-gray-50 hover:text-indigo-600">
                                            <Cog6ToothIcon
                                                class="h-6 w-6 shrink-0 text-gray-400 group-hover:text-indigo-600"
                                                aria-hidden="true" />
                                            Settings
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </DialogPanel>
                </TransitionChild>
            </div>
        </Dialog>
    </TransitionRoot>

    <!-- Static sidebar for desktop -->
    <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
        <!-- Sidebar component, swap this element with another sidebar if you like -->
        <div class="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 bg-white px-6 pb-4">
            <div class="flex h-16 shrink-0 items-center">
                <!-- <img class="h-8 w-auto" src="https://tailwindui.com/plus/img/logos/mark.svg?color=indigo&shade=600"
                        alt="Your Company" /> -->
                <ApplicationLogo />
            </div>
            <nav class="flex flex-1 flex-col">
                <ul role="list" class="flex flex-1 flex-col gap-y-3">
                    <li>
                        <ul role="list" class="-mx-2">
                            <li v-for="item in defaultnav" :key="item.name">
                                <Link :href="item.href"
                                    :class="[item.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-600', 'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6']"
                                    :method="item.method" as="button">
                                <component :is="item.icon"
                                    :class="[item.current ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600', 'h-6 w-6 shrink-0']"
                                    aria-hidden="true" />
                                {{ item.name }}
                                </Link>
                            </li>
                            <li v-if="$hasRoles('student')" v-for="item in navigation" :key="item.name">
                                <Link :href="item.href"
                                    :class="[item.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-600', 'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6']"
                                    :method="item.method" as="button">
                                <component :is="item.icon"
                                    :class="[item.current ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600', 'h-6 w-6 shrink-0']"
                                    aria-hidden="true" />
                                {{ item.name }}
                                </Link>
                            </li>
                            <li v-if="!$hasRoles('student')" v-for="item in adminNavigation" :key="item.name">
                                <Link :href="item.href"
                                    :class="[item.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-600', 'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6']"
                                    :method="item.method" as="button">
                                <component :is="item.icon"
                                    :class="[item.current ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600', 'h-6 w-6 shrink-0']"
                                    aria-hidden="true" />
                                {{ item.name }}
                                </Link>
                            </li>
                        </ul>
                    </li>
                    <div v-if="$hasRoles(['admin_prodi', 'admin_fakultas'])">
                        <div class="text-xs font-semibold leading-6 text-gray-400">Master Data</div>
                        <li v-for="item in masterNavigation" :key="item.name" class="-mx-2">
                            <Link v-if="!item.children" :href="item.href"
                                :class="[item.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-600', 'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6']"
                                :method="item.method" as="button">
                            <component :is="item.icon"
                                :class="[item.current ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600', 'h-6 w-6 shrink-0']"
                                aria-hidden="true" />
                            {{ item.name }}
                            </Link>
                            <Disclosure as="div" v-else :defaultOpen="isOpenDisclosure(item)" v-slot="{ open }">
                                <DisclosureButton
                                    :class="[item.current ? 'bg-gray-50 text-indigo-600' : 'hover:bg-gray-50 hover:text-indigo-600', 'group flex w-full items-center gap-x-3 rounded-md p-2 text-left text-sm font-semibold leading-6 text-gray-700']">
                                    <component :is="item.icon"
                                        :class="[item.current ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600', 'h-6 w-6 shrink-0']"
                                        aria-hidden=" true" />
                                    {{ item.name }}
                                    <ChevronRightIcon
                                        :class="[open ? 'rotate-90 text-gray-500' : 'text-gray-400', 'ml-auto h-5 w-5 shrink-0']"
                                        aria-hidden="true" />
                                </DisclosureButton>
                                <DisclosurePanel as="ul" class="mt-1 px-2">
                        <li v-for="subItem in item.children" :key="subItem.name">
                            <!-- 44px -->
                            <Link :href="subItem.href"
                                :class="[subItem.current ? 'bg-gray-50 text-indigo-600' : 'hover:bg-gray-50 hover:text-indigo-600', 'flex w-full rounded-md gap-x-3 py-2 pl-9 pr-2 text-sm leading-6 text-gray-700']"
                                :method="subItem.method" as="button">
                            <component :is="subItem.icon"
                                :class="[subItem.current ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600', 'h-6 w-6 shrink-0']"
                                aria-hidden="true" />
                            {{ subItem.name }}
                            </Link>
                        </li>
                        </DisclosurePanel>
                        </Disclosure>
                        </li>
                    </div>
                    <div v-if="$hasPerms('access-control')">
                        <div class="text-xs font-semibold leading-6 text-gray-400">Access Control</div>
                        <li>
                            <ul role="list" class="-mx-2 mt-2">
                                <li v-for="item in accessControlNavigation" :key="item.name">
                                    <Link :href="item.href"
                                        :class="[item.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-600', 'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6']"
                                        :method="item.method" as="button">
                                    <span
                                        :class="[item.current ? 'border-indigo-600 text-indigo-600' : 'border-gray-200 text-gray-400 group-hover:border-indigo-600 group-hover:text-indigo-600', 'flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border bg-white text-[0.625rem] font-medium']">{{
                                            item.initial }}</span>
                                    <span class="truncate">{{ item.name }}</span>
                                    </Link>
                                </li>
                            </ul>
                        </li>
                    </div>
                    <!-- <li class="mt-auto">
                            <a href="#"
                                class="group -mx-2 flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6 text-gray-700 hover:bg-gray-50 hover:text-indigo-600">
                                <Cog6ToothIcon class="h-6 w-6 shrink-0 text-gray-400 group-hover:text-indigo-600"
                                    aria-hidden="true" />
                                Settings
                            </a>
                        </li> -->
                </ul>
            </nav>
        </div>
    </div>

    <div class="lg:pl-72">
        <div
            class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
            <button type="button" class="-m-2.5 p-2.5 text-gray-700 lg:hidden" @click="sidebarOpen = true">
                <span class="sr-only">Open sidebar</span>
                <Bars3Icon class="h-6 w-6" aria-hidden="true" />
            </button>

            <!-- Separator -->
            <div class="h-6 w-px bg-gray-200 lg:hidden" aria-hidden="true" />

            <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                <form class="relative flex flex-1" action="#" method="GET">
                    <!-- <label for="search-field" class="sr-only">Search</label>
                        <MagnifyingGlassIcon
                            class="pointer-events-none absolute inset-y-0 left-0 h-full w-5 text-gray-400"
                            aria-hidden="true" />
                        <input id="search-field"
                            class="block h-full w-full border-0 py-0 pl-8 pr-0 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm"
                            placeholder="Search..." type="search" name="search" /> -->
                </form>
                <div class="flex items-center gap-x-4 lg:gap-x-6">
                    <!-- <button type="button" class="-m-2.5 p-2.5 text-gray-400 hover:text-gray-500">
                            <span class="sr-only">View notifications</span>
                            <BellIcon class="h-6 w-6" aria-hidden="true" />
                        </button> -->
                    <LinkNotif />

                    <!-- Separator -->
                    <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-200" aria-hidden="true" />

                    <!-- Profile dropdown -->
                    <Menu as="div" class="relative">
                        <MenuButton class="-m-1.5 flex items-center p-1.5">
                            <span class="sr-only">Open user menu</span>
                            <!-- <img class="h-8 w-8 rounded-full bg-gray-50"
                                    src="https://unsplash.com/illustrations/a-man-in-a-doctors-uniform-with-a-stethoscope-ixgbfI3BAgs?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                                    alt="" /> -->
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="h-8 w-8 rounded-full bg-gray-50">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>

                            <span class="hidden lg:flex lg:items-center">
                                <span class="ml-4 text-sm font-semibold leading-6 text-gray-900" aria-hidden="true">{{
                                    $page.props.auth.user.fullname }}</span>
                                <ChevronDownIcon class="ml-2 h-5 w-5 text-gray-400" aria-hidden="true" />
                            </span>
                        </MenuButton>
                        <transition enter-active-class="transition ease-out duration-100"
                            enter-from-class="transform opacity-0 scale-95"
                            enter-to-class="transform opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-75"
                            leave-from-class="transform opacity-100 scale-100"
                            leave-to-class="transform opacity-0 scale-95">
                            <MenuItems
                                class="absolute right-0 z-10 mt-2.5 w-32 origin-top-right rounded-md bg-white py-2 shadow-lg ring-1 ring-gray-900/5 focus:outline-none">
                                <MenuItem v-for="item in userNavigation" :key="item.name" v-slot="{ active }">
                                <Link :href="item.href" :method="item.method" as="button"
                                    :class="[active ? 'bg-gray-100' : '', 'block w-full text-left px-3 py-1 text-sm leading-6 text-gray-900']">
                                {{ item.name }}
                                </Link>
                                </MenuItem>
                                <!-- <div class="text-xs font-semibold leading-6 text-gray-400 px-3">Available Roles
                                    </div>
                                    <MenuItem v-for="role in availRoles" :key="role.name" v-slot="{ active }">
                                    <Link :href="route('users.role-as', { role: role })" method="patch" as="button"
                                        :class="[active ? 'bg-gray-100' : '', 'block w-full text-left px-3 py-1 text-sm leading-6 text-gray-900']">
                                    {{ role.name }}
                                    </Link>
                                    </MenuItem> -->
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
            </div>
        </div>

        <main class="py-5 px-5">
            <header class="flex items-center justify-between border-b border-white/5 px-2 py-2 sm:px-4 sm:py-4 lg:px-6">
                <!-- Page Heading -->
                <h1 class="font-semibold text-xl text-gray-800 leading-tight">
                    <slot name="header" />
                </h1>
            </header>
            <div class="px-2 sm:px-4 lg:px-6">
                <!-- Your content -->
                <slot />
            </div>
        </main>
    </div>
</template>