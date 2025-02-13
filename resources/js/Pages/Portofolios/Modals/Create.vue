<script setup>
import { ref, watch } from 'vue'
import Modal from '@/Components/Modal.vue';

import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import InputUpload from '@/Components/InputUpload.vue';

const props = defineProps({
    show: Boolean,
});

const form = useForm({
    name: '',
    description: '',
    document: null,
    portofolio_document_path: '',
});

watch(
    () => props.show,
    (newValue, oldValue) => {
        if (newValue) {
            form.reset();
            form.clearErrors();
        }
    },
    { immediate: true }
);

const submit = () => {
    form.portofolio_document_path = form.document ? URL.createObjectURL(form.document) : null;
    form.post(route('portofolios.store'), {
        onSuccess: (data) => {
            form.reset();
            form.clearErrors();
        },
    });
};

</script>
<template>
    <Modal :show="show" @close="form.reset(), form.errors = {}">
        <div class="divide-y divide-gray-200 overflow-hidden rounded-lg bg-white shadow">
            <div class="px-4 py-5 sm:px-6">
                <!-- Content goes here -->
                <!-- We use less vertical padding on card headers on desktop than on body sections -->
                <h2 class="text-lg font-medium text-gray-900">
                    Tambah Datadiri
                </h2>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <!-- Content goes here -->
                <form @submit.prevent="submit" class="mt-1 text-sm text-gray-600">
                    <div>
                        <InputLabel for="name" value="Name" />

                        <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required
                            autofocus autocomplete="name" />

                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                    <div class="mt-2">
                        <InputLabel for="description" value="Description" />

                        <TextInput id="description" type="text" class="mt-1 block w-full" v-model="form.description"
                            required autofocus autocomplete="description" />

                        <InputError class="mt-2" :message="form.errors.description" />
                    </div>
                    <div class="mt-2">
                        <InputUpload v-model="form.document" :initialDocumentPath="form.portofolio_document_path"
                            ref="fileUploader" />
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
                    </div>
                </form>
            </div>
        </div>
    </Modal>
</template>