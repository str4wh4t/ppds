<script setup>
import { ref, watch } from 'vue'
import Modal from '@/Components/Modal.vue';

import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import SelectInput from '@/Components/SelectInputBasic.vue';
import MultiselectBasic from '@/Components/MultiselectBasic.vue';

const props = defineProps({
    show: Boolean,
});

const userKaprodi = ref(null);

const kaprodi_list = usePage().props.kaprodi_list;

const kaprodiList = kaprodi_list.map(user => ({
    ...user,
    name: user.fullname, // Mengganti fullname dengan name
    fullname: undefined // Menghapus key fullname
}));

const admin_list = usePage().props.admin_list;

const adminList = admin_list.map(user => ({
    ...user,
    name: user.fullname, // Mengganti fullname dengan name
    fullname: undefined // Menghapus key fullname
}));

const form = useForm({
    name: '',
    kaprodi_user_id: '',
    unit_admins: [],
});

watch(
    () => props.show,
    (newValue, oldValue) => {
        if (newValue) {
            form.reset();
            form.clearErrors();
            userKaprodi.value = null;
        }
    },
    { immediate: true }
);

const submit = () => {
    form.kaprodi_user_id = userKaprodi.value?.id ?? '';
    form.post(route('units.store'), {
        onSuccess: (data) => {
            form.reset();
            form.clearErrors();
            userKaprodi.value = null;
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
                    Create Unit
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
                        <InputLabel for="kaprodi_user_id" value="Kaprodi" />

                        <SelectInput id="kaprodi_user_id" class="mt-1 block w-full" v-model="userKaprodi"
                            :options="kaprodiList" required autofocus autocomplete="kaprodi_user_id" />

                        <InputError class="mt-2" :message="form.errors.kaprodi_user_id" />
                    </div>
                    <div class="mt-2">
                        <InputLabel for="admin_user_id" value="Admin" />

                        <MultiselectBasic class="mt-1 block w-full" :key="form.name" :options="adminList"
                            v-model="form.unit_admins" />

                        <InputError class="mt-2" :message="form.errors.unit_admins" />
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