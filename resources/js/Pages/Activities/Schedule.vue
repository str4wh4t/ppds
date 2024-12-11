<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Link, usePage } from '@inertiajs/vue3';
import ModalCreate from './Modals/Create.vue';
import ModalUpdate from './Modals/Update.vue';
import CreateButton from '@/Components/CreateButton.vue';
import { ChevronLeftIcon, ChevronRightIcon, MagnifyingGlassIcon } from '@heroicons/vue/20/solid';
import { ref, computed } from 'vue';
import { CalendarDaysIcon, PrinterIcon } from '@heroicons/vue/24/outline';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import moment from 'moment';

const pdfIframe = ref(null);

const printPDF = () => {
    // Mengambil dokumen dalam iframe dan memanggil perintah cetak
    if (pdfIframe.value && pdfIframe.value.contentWindow) {
        pdfIframe.value.contentWindow.print();
    }
};

const { month_number, year } = usePage().props;

const labelMonth = ref(moment().month(month_number).format('MMMM'));
const labelYear = ref(year);

</script>

<template>

    <Head title="Schedule" />
    <AuthenticatedLayout>
        <template #header>
            Jadwal
        </template>
        <div class="lg:flex lg:h-full lg:flex-col mt-4">
            <header class="flex items-center justify-between border-b border-gray-200 pb-5 lg:flex-none">
                <Link
                    :href="route('activities.calendar', { user: usePage().props.auth.user, month_number: month_number, year: year })"
                    class="hidden sm:inline-flex items-center gap-x-1.5 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                    type="button">
                <CalendarDaysIcon class="-ml-0.5 h-4 w-4 u" aria-hidden="true" /> Lihat Kalender
                </Link>
                <h1 class="text-base font-semibold leading-6 text-gray-900">
                    <span>Jadwal {{ labelMonth }} {{ labelYear }}</span>
                </h1>
                <div class="flex items-center">
                    <PrimaryButton @click="printPDF">
                        <PrinterIcon class="-ml-0.5 h-4 w-4 u mr-1" aria-hidden="true" /> Cetak Jadwal
                    </PrimaryButton>
                </div>
            </header>
            <div class="sm:flex sm:items-center w-full">
                <div class="pdf-container">
                    <iframe ref="pdfIframe" :src="$page.props.schedule" width="100%" height="1000px"
                        frameborder="0"></iframe>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.pdf-container {
    width: 100%;
    /* Ensures the PDF takes the full width of the container */
    max-width: 100vw;
    /* Limits it to the viewport width */
    height: auto;
    padding: 20px;
    /* Optional padding for better spacing */
}

iframe {
    width: 100%;
    /* Makes sure the iframe also stretches fully */
    height: 1000px;
    /* Adjust the height based on your needs */
}
</style>