<script setup>
import { ref, watch } from 'vue'
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { CloudArrowUpIcon, PrinterIcon, XCircleIcon } from '@heroicons/vue/24/outline';
import WarningButton from '@/Components/WarningButton.vue';


const emit = defineEmits(['exitDocument']);
const props = defineProps({
    document: Object,
    show: Boolean,
});

const form = useForm({
    document: null,
});

watch(
    () => props.show,
    (newValue, oldValue) => {
        if (!newValue) {
        }
        if (newValue) {
            form.document = props.document;
        }
    },
    { immediate: true }
);

// const submit = () => {
//     form.post(route('#', { user: props.user }), {
//         onSuccess: (data) => {
//             form.clearErrors();
//             emit('exitUpdate');
//         }
//     })
// };

const pdfIframe = ref(null);

const printPDF = () => {
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
                    Attachments : {{ $snakeCaseText(form.document.title) }}.pdf
                </h2>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <!-- Content goes here -->
                <form @submit.prevent="submit" class="mt-1 text-sm text-gray-600">
                    <div class="sm:flex sm:items-center w-full">
                        <iframe ref="pdfIframe" :src="$storagePath(form.document.path)" width="100%" height="1000px"
                            frameborder="0"></iframe>
                    </div>
                    <div class="flex items-center justify-center mt-4">
                        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                            <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
                        </Transition>
                        <WarningButton class="ml-4" @click="emit('exitDocument')" :disabled="form.processing"
                            type="button">
                            <CloudArrowUpIcon class="-ml-0.5 h-4 w-4 u mr-1" aria-hidden="true" /> Tutup
                        </WarningButton>
                        <PrimaryButton class="ml-4" @click="printPDF" type="button">
                            <PrinterIcon class="-ml-0.5 h-4 w-4 u mr-1" aria-hidden="true" /> Cetak
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </Modal>
</template>