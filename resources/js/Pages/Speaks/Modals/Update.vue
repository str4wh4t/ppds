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
import InputUpload from '@/Components/InputUpload.vue';
import QuillEditor from '@/Components/QuillEditor.vue';
import { XCircleIcon } from '@heroicons/vue/24/outline';

const emit = defineEmits(['exitUpdate']);
const props = defineProps({
    speak: Object,
    show: Boolean,
});

const showConfirmDelete = ref(false);

const form = useForm({
    speak_title: '',
    description: '',
    document: null,
    speak_document_path: null,
});

watch(
    () => props.show,
    (newValue, oldValue) => {
        if (!newValue) {
            showConfirmDelete.value = false;
        }
        if (newValue) {
            form.clearErrors();
            form.speak_title = props.speak.speak_title;
            form.description = props.speak.description;
            // form.document = '';
            form.speak_document_path = props.speak.speak_document_path;
        }
    },
    { immediate: true }
);

const submit = () => {
    if (form.description.trim() === '<p><br></p>') {
        form.description = ''; // Mengosongkan konten jika hanya berisi paragraf kosong
    }
    if (form.document !== null) { // << ini karena document di buat nullable di database
        form.speak_document_path = URL.createObjectURL(form.document);
    }
    form.post(route('speaks.update', { speak: props.speak }), {
        onSuccess: (data) => {
            form.clearErrors();
            showConfirmDelete.value = false;
            // form.document = null;
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

const deleteItem = () => {
    form.delete(route('speaks.destroy', { speak: props.speak }), {
        onSuccess: (data) => {
            form.reset();
            showConfirmDelete.value = false;
            emit('exitUpdate');
        },
    });
}

const fileUploader = ref(null);

const clearDocument = () => {
    fileUploader.value?.removeDocument(); // Memanggil removeDocument dari FileUploader
    form.speak_document_path = null;
    form.document = null;
};

</script>
<template>
    <Modal :show="show" @close="form.reset(), form.errors = {}">
        <div id="innerModal" class="divide-y divide-gray-200 overflow-hidden rounded-lg bg-white shadow">
            <div class="px-4 py-5 sm:px-6">
                <!-- Content goes here -->
                <!-- We use less vertical padding on card headers on desktop than on body sections -->
                <h2 class="text-lg font-medium text-gray-900">
                    Update Laporan
                </h2>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <!-- Content goes here -->
                <form @submit.prevent="submit" class="mt-1 text-sm text-gray-600">
                    <div>
                        <InputLabel for="speak_title" value="Judul" />

                        <TextInput id="speak_title" type="text" class="mt-1 block w-full" v-model="form.speak_title"
                            required autofocus autocomplete="speak_title" />

                        <InputError class="mt-2" :message="form.errors.speak_title" />
                    </div>
                    <div class="mt-2">
                        <InputLabel for="description" value="Description" />

                        <QuillEditor id="description" class="mt-1 block w-full" v-model="form.description" />

                        <InputError class="mt-2" :message="form.errors.description" />
                    </div>
                    <div class="mt-2">
                        <InputUpload v-model="form.document" :fileType="'jpeg'"
                            :initialDocumentPath="form.speak_document_path" ref="fileUploader" />
                        <InputError class="mt-2" :message="form.errors.document" />
                    </div>
                    <div class="flex items-center justify-end mt-4">
                        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                            <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
                        </Transition>
                        <PrimaryButton class="ml-4" :disabled="form.processing">
                            Save
                        </PrimaryButton>
                        <DangerButton @click="clearDocument" v-if="form.speak_document_path" type="button" class="ml-2"
                            :disabled="form.processing">
                            <XCircleIcon class="-ml-0.5 h-4 w-4 u mr-1" aria-hidden="true" /> Hapus Dokumen
                        </DangerButton>
                        <DangerButton type="button" class="ml-2" :disabled="form.processing"
                            @click="onShowConfirmDelete">
                            Delete
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
                            <DangerButton class="ml-2" :disabled="form.processing" @click="deleteItem">
                                Sure
                            </DangerButton>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </Modal>
</template>