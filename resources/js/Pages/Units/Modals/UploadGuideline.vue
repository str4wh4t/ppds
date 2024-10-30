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
import { CloudArrowUpIcon, XCircleIcon } from '@heroicons/vue/24/outline';


const emit = defineEmits(['exitUpdate']);
const props = defineProps({
    unit: Object,
    show: Boolean,
});

const showConfirmDelete = ref(false);

const fileUploader = ref(null);

const form = useForm({
    unit_id: '',
    document: null,
    document_path: '',
});


watch(
    () => props.show,
    (newValue, oldValue) => {
        if (!newValue) {
            showConfirmDelete.value = false;
        }
        if (newValue) {
            form.unit_id = props.unit.id;
            form.document = props.unit.document;
            form.document_path = props.unit.guideline_document_path;
        }
    },
    { immediate: true }
);

const submit = () => {
    form.post(route('units.upload-document-guideline', { unit: props.unit }), {
        forceFormData: true,
        onSuccess: (data) => {
            form.clearErrors();
            emit('exitUpdate');
        }
    })
};

const confirmDeleteDialog = ref(null); // Ref untuk bagian konfirmasi

const onShowConfirmDelete = () => {
    showConfirmDelete.value = true;
    nextTick(() => {
        confirmDeleteDialog.value.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

const clearDocument = () => {
    form.delete(route('units.delete-document-guideline', { unit: props.unit }), {
        onSuccess: (data) => {
            fileUploader.value?.removeDocument(); // Memanggil removeDocument dari FileUploader
            form.document_path = '';
            showConfirmDelete.value = false;

        }
    })
};

</script>
<template>
    <Modal :show="show" @close="form.reset(), form.errors = {}">
        <div id="innerModal" class="divide-y divide-gray-200 overflow-hidden rounded-lg bg-white shadow">
            <div class="px-4 py-5 sm:px-6">
                <!-- Content goes here -->
                <!-- We use less vertical padding on card headers on desktop than on body sections -->
                <h2 class="text-lg font-medium text-gray-900">
                    Upload Document
                </h2>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <!-- Content goes here -->
                <form @submit.prevent="submit" class="mt-1 text-sm text-gray-600">
                    <div>
                        <InputUpload v-model="form.document" :initialDocumentPath="form.document_path"
                            ref="fileUploader" />
                    </div>
                    <div v-if="form.document_path || form.document" class="flex items-center justify-center mt-4">
                        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                            <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
                        </Transition>
                        <PrimaryButton class="ml-4" v-if="!form.document_path" :disabled="form.processing">
                            <CloudArrowUpIcon class="-ml-0.5 h-5 w-5 mr-1" aria-hidden="true" /> Upload
                        </PrimaryButton>
                        <DangerButton @click="onShowConfirmDelete" v-if="form.document_path" type="button" class="ml-2"
                            :disabled="form.processing">
                            <XCircleIcon class="-ml-0.5 h-5 w-5 mr-1" aria-hidden="true" /> Delete
                        </DangerButton>
                    </div>
                    <div v-if="showConfirmDelete" ref="confirmDeleteDialog" tabindex="-1"
                        class="flex flex-col items-center justify-center mt-4 border-2 border-yellow-400 bg-yellow-50 p-4 text-center">
                        <div>
                            Sure want to delete, it cannot be undone?
                        </div>
                        <div class="flex mt-4">
                            <SecondaryButton class="ml-2" :disabled="form.processing"
                                @click="showConfirmDelete = false">
                                Cancel
                            </SecondaryButton>
                            <DangerButton class="ml-2" :disabled="form.processing" @click="clearDocument">
                                Sure
                            </DangerButton>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </Modal>
</template>