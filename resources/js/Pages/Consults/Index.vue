<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Link, usePage } from '@inertiajs/vue3';
import ModalCreate from './Modals/Create.vue';
import ModalUpdate from './Modals/Update.vue';
import ModalReply from './Modals/Reply.vue';
import ModalDocument from '@/Components/ModalDocument.vue';
import CreateButton from '@/Components/CreateButton.vue';
import { CheckCircleIcon, ChevronLeftIcon, ChevronRightIcon, MagnifyingGlassIcon, XCircleIcon } from '@heroicons/vue/20/solid';
import { ref, computed } from 'vue';
import { StarIcon } from '@heroicons/vue/20/solid'
import { PaperClipIcon } from '@heroicons/vue/24/outline';


const consults = computed(() => usePage().props.consults);

const filters = ref({ search: usePage().props.filters.search ?? '' });

const isCreate = ref(false);
const isUpdate = ref(false);
const selectedItem = ref({});

const openUpdate = (consult) => {
    selectedItem.value = consult;
    isUpdate.value = true;
};

const closeUpdate = () => {
    isUpdate.value = false;
};

const searchPosts = () => {
    router.get(route('consults.index', { user: usePage().props.auth.user }), { search: filters.value.search }, { replace: true });
};

const showDocument = ref(false);
const closeDocument = () => {
    showDocument.value = false;
};

const selectedDocument = ref({
    path: '',
    title: '',
});
const openItem = (document) => {
    selectedDocument.value.path = document.path;
    selectedDocument.value.title = document.title;
    showDocument.value = true;
};

const isReply = ref(false);
const openReply = (consult) => {
    selectedItem.value = consult;
    isReply.value = true;
};
const closeReply = () => {
    isReply.value = false;
};

</script>

<template>

    <Head title="Consult List" />
    <AuthenticatedLayout>
        <template #header>
            Daftar Konsultasi
        </template>
        <div class="divide-y divide-gray-100 rounded-md border border-red-400 px-5 py-4 mt-4">
            Dosen Pembimbing : <span class="font-bold">{{ usePage().props.dosbing_user?.fullname }}</span>
        </div>
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
                    Tambah konsultasi
                </CreateButton>
            </div>
        </div>
        <div class="space-y-10 divide-y divide-gray-200 border-b border-t border-gray-200 pb-10 mt-5">
            <div v-if="consults.data.length !== 0" v-for="consult in consults.data" :key="consult.id" class="text-sm">
                <div class="pt-5 lg:grid lg:grid-cols-12 lg:gap-x-8">
                    <div
                        class="lg:col-span-8 lg:col-start-5 xl:col-span-9 xl:col-start-4 xl:grid xl:grid-cols-3 xl:items-start xl:gap-x-8">
                        <div class="mt-2 lg:mt-2 xl:col-span-3 xl:mt-0">
                            <div class="flex items-center justify-start">
                                <h3 class="font-medium text-gray-900">{{ consult.consult_title }}</h3>
                                <a v-if="!consult.reply_at && $hasRoles('student')" @click="openUpdate(consult)"
                                    href="#"
                                    class="text-sm ml-5 font-bold bg-yellow-300 px-3 font-medium text-indigo-600 hover:text-indigo-500">Edit</a>
                                <a v-if="!consult.reply_at && !$hasRoles('student')" @click="openReply(consult)"
                                    href="#"
                                    class="text-sm ml-5 font-bold bg-yellow-300 px-3 font-medium text-indigo-600 hover:text-indigo-500">Jawab</a>
                            </div>
                            <div class="mt-3 space-y-6  text-gray-500" v-html="consult.description" />
                            <div v-if="consult.consult_document_size > 0"
                                class="px-4 py-4 sm:grid sm:grid-cols-4 sm:gap-4 sm:px-0">
                                <dt class="text-sm/6 font-medium text-gray-900">Attachments</dt>
                                <dd class="mt-2 text-sm text-gray-900 sm:col-span-3 sm:mt-0">
                                    <ul role="list" class="divide-y divide-gray-100 rounded-md border border-gray-200">
                                        <li class="flex items-center justify-between py-4 pl-4 pr-5 text-sm/6">
                                            <div class="flex w-0 flex-1 items-center">
                                                <PaperClipIcon class="h-4 w-4 u flex-shrink-0 text-gray-400"
                                                    aria-hidden="true" />
                                                <div class="ml-4 flex min-w-0 flex-1 gap-2">
                                                    <span class="truncate font-medium">{{
                                                        $snakeCaseText(consult.consult_title)
                                                        }}.pdf</span>
                                                    <span class="flex-shrink-0 text-gray-400">{{
                                                        consult.consult_document_size
                                                        }} mb</span>
                                                </div>
                                            </div>
                                            <div class="ml-4 flex-shrink-0">
                                                <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500"
                                                    @click="openItem({ path: consult.consult_document_path, title: consult.consult_title })">Download</a>
                                            </div>
                                        </li>
                                    </ul>
                                </dd>
                            </div>
                        </div>
                    </div>
                    <div
                        class="mt-2 flex items-center lg:col-span-4 lg:col-start-1 lg:row-start-1 lg:mt-0 lg:flex-col lg:items-start xl:col-span-3">
                        <p class="font-medium text-gray-900">{{ consult.user.fullname }}</p>
                        <time :datetime="consult.created_at"
                            class="ml-4 pl-4 text-gray-500 lg:ml-0 lg:mt-2 lg:border-0 lg:pl-0">{{
                                $formatDate({ date: consult.created_at, formatOutput: "DD/MM/YYYY hh:mm" }) }}</time>
                        <div class="ml-4 pl-4 lg:ml-0 lg:mt-2 lg:border-0 lg:pl-0 flex items-center">
                            <StarIcon v-for="rating in [0, 1, 2, 3, 4]" :key="rating"
                                :class="[consult.rating > rating ? 'text-yellow-400' : 'text-white', 'h-4 w-4 u flex-shrink-0']"
                                aria-hidden="true" />
                        </div>
                    </div>
                </div>
                <div v-if="consult.reply_at" class="pt-5 lg:grid lg:grid-cols-12 lg:gap-x-8 bg-yellow-50">
                    <div
                        class="lg:col-span-8 lg:col-start-5 xl:col-span-9 xl:col-start-4 xl:grid xl:grid-cols-3 xl:items-start xl:gap-x-8">
                        <div class="mt-2 lg:mt-2 xl:col-span-3 xl:mt-0">
                            <div class="flex items-center justify-start">
                                <h3 class="font-medium text-gray-900">{{ consult.reply_title }}</h3>
                                <a v-if="!$hasRoles('student')" @click="openReply(consult)" href="#"
                                    class="text-sm ml-5 font-bold bg-yellow-300 px-3 font-medium text-indigo-600 hover:text-indigo-500">Edit</a>
                            </div>
                            <div class="mt-3 space-y-6 text-gray-500" v-html="consult.reply" />
                            <div v-if="consult.reply_document_size > 0"
                                class="px-4 py-4 sm:grid sm:grid-cols-4 sm:gap-4 sm:px-0">
                                <dt class="text-sm/6 font-medium text-gray-900">Attachments</dt>
                                <dd class="mt-2 text-sm text-gray-900 sm:col-span-3 sm:mt-0">
                                    <ul role="list" class="divide-y divide-gray-100 rounded-md border border-gray-200">
                                        <li class="flex items-center justify-between py-4 pl-4 pr-5 text-sm/6">
                                            <div class="flex w-0 flex-1 items-center">
                                                <PaperClipIcon class="h-4 w-4 u flex-shrink-0 text-gray-400"
                                                    aria-hidden="true" />
                                                <div class="ml-4 flex min-w-0 flex-1 gap-2">
                                                    <span class="truncate font-medium">{{
                                                        $snakeCaseText(consult.reply_title)
                                                    }}.pdf</span>
                                                    <span class="flex-shrink-0 text-gray-400">{{
                                                        consult.reply_document_size
                                                        }} mb</span>
                                                </div>
                                            </div>
                                            <div class="ml-4 flex-shrink-0">
                                                <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500"
                                                    @click="openItem({ path: consult.reply_document_path, title: consult.reply_title })">Download</a>
                                            </div>
                                        </li>
                                    </ul>
                                </dd>
                            </div>
                        </div>
                    </div>
                    <div
                        class="mt-2 flex items-center lg:col-span-4 lg:col-start-1 lg:row-start-1 lg:mt-0 lg:flex-col lg:items-start xl:col-span-3">
                        <p class="font-medium text-gray-900">{{ consult.dosbing.fullname }}</p>
                        <time :datetime="consult.reply_at"
                            class="ml-4 border-l border-gray-200 pl-4 text-gray-500 lg:ml-0 lg:mt-2 lg:border-0 lg:pl-0">{{
                                $formatDate({ date: consult.reply_at, formatOutput: "DD/MM/YYYY hh:mm" }) }}</time>
                    </div>
                </div>
            </div>
            <div v-else>
                <div class="text-center text-gray-500 mt-4 bg-yellow-50">
                    No data found.
                </div>
            </div>
        </div>
        <ModalCreate :show="isCreate" @close="isCreate = false" />
        <ModalUpdate :consult="selectedItem" :show="isUpdate" @close="closeUpdate" @exitUpdate="closeUpdate" />
        <ModalReply :consult="selectedItem" :show="isReply" @close="closeReply" @exitReply="closeReply" />
        <ModalDocument :document="selectedDocument" :show="showDocument" @close="closeDocument"
            @exitDocument="closeDocument" />
    </AuthenticatedLayout>
</template>