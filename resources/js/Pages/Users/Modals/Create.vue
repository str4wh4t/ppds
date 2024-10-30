<script setup>
import { ref, watch } from 'vue'
import Modal from '@/Components/Modal.vue';

import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';

const roles = usePage().props.roles;

const props = defineProps({
    show: Boolean,
});

const form = useForm({
    fullname: '',
    username: '',
    identity: '',
    email: '',
    roles: [],
});

// const props.roles = ref([
//     { title: 'AdminProdi', description: 'Pengadminstrasi perkantoran pada program studi.', selected: true },
//     { title: 'KaProdi', description: 'Penanggung jawab pada program studi.', selected: false },
// ]);

const submit = () => {
    form.post(route('users.store'), {
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
                    Create User
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
                        <InputLabel for="email" value="Email" />

                        <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required
                            autocomplete="email" />

                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>
                    <div class="mt-2">
                        <InputLabel for="role" value="Roles" />
                        <div class="ml-2 mt-1 block w-full">
                            <fieldset>
                                <legend class="sr-only">Roles</legend>
                                <InputError class="mt-2" :message="form.errors.roles" />
                                <div class="grid grid-cols-3 mt-2">
                                    <div class="space-y-5" v-for="role in roles" :key="role.title" :value="role">
                                        <div class="relative flex items-start">
                                            <div class="flex h-6 items-center">
                                                <input :id="role.name" :aria-describedby="role.name + '-description'"
                                                    type="checkbox" :value="role.name" v-model="form.roles"
                                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600" />
                                            </div>
                                            <div class="ml-3 text-sm leading-6">
                                                <label :for="role.name" class="font-medium text-gray-900">{{ role.name
                                                    }}</label>
                                                <!-- <p :id="role.title + '-description'" class="text-gray-500">{{
                                                role.description }}
                                            </p> -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
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