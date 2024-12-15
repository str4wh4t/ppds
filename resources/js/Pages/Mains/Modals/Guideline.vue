<script setup>
import { ref, watch, nextTick } from 'vue'
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { CloudArrowUpIcon, PrinterIcon, XCircleIcon } from '@heroicons/vue/24/outline';
import WarningButton from '@/Components/WarningButton.vue';
// import VuePdfEmbed from 'vue-pdf-embed'


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

const cetakPdf = () => {
    // Mengambil dokumen dalam iframe dan memanggil perintah cetak
    if (pdfFrame.value && pdfFrame.value.contentWindow) {
        pdfFrame.value.contentWindow.print();
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
                        <iframe ref="pdfFrame" :src="$storagePath($page.props.guideline)" width="100%" height="1000px"
                            frameborder="0"></iframe>
                        <!-- <VuePdfEmbed width="800" ref="pdfFrame" scale="5"
                            :source="$storagePath($page.props.guideline)" /> -->
                    </div>
                    <div class="flex items-center justify-center mt-4">
                        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                            <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
                        </Transition>
                        <WarningButton class="ml-4" :disabled="form.processing" type="submit">
                            <CloudArrowUpIcon class="-ml-0.5 h-4 w-4 u mr-1" aria-hidden="true" /> Sudah Baca
                        </WarningButton>
                        <PrimaryButton class="ml-4" @click="cetakPdf" type="button">
                            <PrinterIcon class="-ml-0.5 h-4 w-4 u mr-1" aria-hidden="true" /> Download
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </Modal>
</template>
<style scoped>
.vue-pdf-embed {
    margin: 0 auto;
}

.vue-pdf-embed__page {
    margin-bottom: 8px;
    box-shadow: 0 2px 8px 4px rgba(0, 0, 0, 0.1);
}
</style>