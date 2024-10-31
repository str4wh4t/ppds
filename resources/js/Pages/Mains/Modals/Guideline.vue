<script setup>
import { ref, watch, nextTick } from 'vue'
import Modal from '@/Components/Modal.vue';

import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInputBasic.vue';
import MultiselectBasic from '@/Components/MultiselectBasic.vue';
import InputUpload from '@/Components/InputUpload.vue';
import { CloudArrowUpIcon, PrinterIcon, XCircleIcon } from '@heroicons/vue/24/outline';
import WarningButton from '@/Components/WarningButton.vue';


const emit = defineEmits(['exitUpdate']);
const props = defineProps({
    user: Object,
    show: Boolean,
});

const form = useForm({
    user_id: '',
});

watch(
    () => props.show,
    (newValue, oldValue) => {
        if (!newValue) {
        }
        if (newValue) {
            form.user_id = props.user.id;
        }
    },
    { immediate: true }
);

const submit = () => {
    form.post(route('students.read-guideline', { user: props.user }), {
        onSuccess: (data) => {
            form.clearErrors();
            emit('exitUpdate');
        }
    })
};

const pdfIframe = ref(null);

const printPDF = () => {
    // Mengambil dokumen dalam iframe dan memanggil perintah cetak
    if (pdfIframe.value && pdfIframe.value.contentWindow) {
        pdfIframe.value.contentWindow.print();
    }
};

</script>
<template>
    <Modal :maxWidth="'7xl'" :show="show" @close="form.reset(), form.errors = {}">
        <div id="innerModal" class="divide-y divide-gray-200 overflow-hidden rounded-lg bg-white shadow">
            <div class="px-4 py-5 sm:px-6">
                <!-- Content goes here -->
                <!-- We use less vertical padding on card headers on desktop than on body sections -->
                <h2 class="text-lg font-medium text-gray-900">
                    Buku Pedoman
                </h2>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <!-- Content goes here -->
                <form @submit.prevent="submit" class="mt-1 text-sm text-gray-600">
                    <div class="sm:flex sm:items-center w-full">
                        <iframe ref="pdfIframe" :src="$page.props.guideline" width="100%" height="1000px"
                            frameborder="0"></iframe>
                    </div>
                    <div class="flex items-center justify-center mt-4">
                        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                            <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
                        </Transition>
                        <WarningButton class="ml-4" :disabled="form.processing" type="submit">
                            <CloudArrowUpIcon class="-ml-0.5 h-5 w-5 mr-1" aria-hidden="true" /> Sudah Baca
                        </WarningButton>
                        <PrimaryButton class="ml-4" @click="printPDF" type="button">
                            <PrinterIcon class="-ml-0.5 h-5 w-5 mr-1" aria-hidden="true" /> Cetak Jadwal
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </Modal>
</template>