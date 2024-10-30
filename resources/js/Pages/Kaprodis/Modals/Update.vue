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
import WarningButton from '@/Components/WarningButton.vue';
import MultiselectBasic from '@/Components/MultiselectBasic.vue';

const props = defineProps({
    user: Object,
    show: Boolean,
});

const units = usePage().props.units;

const emit = defineEmits(['exitUpdate']);

const showConfirmDelete = ref(false);

const form = useForm({
    fullname: '',
    username: '',
    identity: '',
    email: '',
    kaprodi_units: [],
});

watch(
    () => props.show,
    (newValue, oldValue) => {
        if (!newValue) {
            showConfirmDelete.value = false;
        }
        if (newValue) {
            form.fullname = props.user.fullname;
            form.username = props.user.username;
            form.identity = props.user.identity;
            form.email = props.user.email;
            form.kaprodi_units = props.user?.kaprodi_units ?? [];
        }
    },
    { immediate: true }
);

const submit = () => {
    form.put(route('kaprodis.update', { user: props.user }), {
        onSuccess: (data) => {
            form.clearErrors();
            showConfirmDelete.value = false;
        },
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
    form.delete(route('users.destroy', { user: props.user }), {
        onSuccess: (data) => {
            form.reset();
            showConfirmDelete.value = false;
            emit('exitUpdate');
        },
    });
}

const onResetPassword = () => {
    form.patch(route('users.reset-password', { user: props.user }), {
        onSuccess: (data) => {
            showConfirmDelete.value = false;
        },
    });
}

</script>
<template>
    <Modal :show="show" @close="form.reset(), form.errors = {}">
        <div id="innerModal" class="divide-y divide-gray-200 overflow-hidden rounded-lg bg-white shadow">
            <div class="px-4 py-5 sm:px-6">
                <!-- Content goes here -->
                <!-- We use less vertical padding on card headers on desktop than on body sections -->
                <h2 class="text-lg font-medium text-gray-900">
                    Update Kaprodi
                </h2>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <!-- Content goes here -->
                <form @submit.prevent="submit" class="mt-1 text-sm text-gray-600">
                    <div>
                        <InputLabel for="fullname" value="Fullname" />

                        <TextInput id="fullname" type="text" class="mt-1 block w-full" v-model="form.fullname" required
                            autofocus autocomplete="fullname" />

                        <InputError class="mt-2" :message="form.errors.fullname" />
                    </div>
                    <div class="mt-2">
                        <InputLabel for="username" value="Username" />

                        <TextInput id="username" type="text" class="mt-1 block w-full" v-model="form.username" required
                            autofocus autocomplete="username" />

                        <InputError class="mt-2" :message="form.errors.username" />
                    </div>
                    <div class="mt-2">
                        <InputLabel for="identity" value="Identity" />

                        <TextInput id="identity" type="text" class="mt-1 block w-full" v-model="form.identity" required
                            autofocus autocomplete="identity" />

                        <InputError class="mt-2" :message="form.errors.identity" />
                    </div>
                    <div class="mt-2">
                        <InputLabel for="kaprodi_units" value="Units" />

                        <MultiselectBasic class="mt-1 block w-full" :key="form.identity" :options="units"
                            v-model="form.kaprodi_units" />

                        <InputError class="mt-2" :message="form.errors.kaprodi_units" />
                    </div>
                    <div class="mt-2">
                        <InputLabel for="email" value="Email" />

                        <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required
                            autocomplete="email" />

                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>
                    <div class="flex items-center justify-end mt-4">
                        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                            <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
                        </Transition>
                        <PrimaryButton class="ml-4" :disabled="form.processing">
                            Save
                        </PrimaryButton>
                        <WarningButton type="button" class="ml-2" :disabled="form.processing" @click="onResetPassword">
                            Reset Password
                        </WarningButton>
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