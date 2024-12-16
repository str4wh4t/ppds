<script setup>
import { ref, watch, nextTick } from 'vue'
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { CloudArrowUpIcon, DocumentArrowUpIcon, PrinterIcon, XCircleIcon } from '@heroicons/vue/24/outline';
import WarningButton from '@/Components/WarningButton.vue';
import ButtonLink from '@/Components/ButtonLink.vue';
// import PdfViewer from '@/Components/PdfViewer.vue';


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

const pdfFrame = ref(null);

// const cetakPdf = () => {
//     if (pdfFrame.value && pdfFrame.value.contentWindow) {
//         pdfFrame.value.contentWindow.print();
//     }
// };

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
                        <iframe ref="pdfFrame" :src="$storagePath($page.props.guideline)" width="100%" height="1000px"
                            frameborder="0"></iframe>
                        <!-- <embed ref="pdfFrame" :src="$storagePath($page.props.guideline)" type="application/pdf"
                            width="100%" height="1000px" frameborder="0" /> -->
                        <!-- <div class="bg-slate-500 p-2 w-full">
                            <VuePdfEmbed width="800" ref="pdfFrame" :source="$storagePath($page.props.guideline)" />
                        </div> -->
                        <!-- <PdfViewer width="800" :source="$storagePath($page.props.guideline)"
                            v-if="$page.props.guideline" /> -->
                    </div>
                    <div class="flex items-center justify-center mt-4">
                        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                            <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
                        </Transition>
                        <WarningButton class="ml-1" :disabled="form.processing" type="submit">
                            <CloudArrowUpIcon class="-ml-0.5 h-4 w-4 u mr-1" aria-hidden="true" /> Sudah Baca
                        </WarningButton>
                        <!-- <PrimaryButton class="ml-1" @click="cetakPdf" type="button">
                            <PrinterIcon class="-ml-0.5 h-4 w-4 u mr-1" aria-hidden="true" /> Download
                        </PrimaryButton> -->
                        <ButtonLink class="ml-1" :href="$storagePath($page.props.guideline)" target="_blank"
                            v-if="$page.props.guideline">
                            <DocumentArrowUpIcon class="-ml-0.5 h-4 w-4 mr-1" aria-hidden="true" />
                            Open PDF
                        </ButtonLink>
                    </div>
                </form>
            </div>
        </div>
    </Modal>
</template>