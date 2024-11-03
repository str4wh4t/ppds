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

const emit = defineEmits(['exitUpdate']);
const props = defineProps({
    unit: Object,
    show: Boolean,
});

const showConfirmDelete = ref(false);

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
    stases: [],
});

const updatedUserKaprodi = ref({
    id: props.unit?.kaprodi_user?.id ?? 0,
    name: props.unit?.kaprodi_user?.fullname ?? ''
});

const stases = usePage().props.stases;

watch(
    () => props.show,
    (newValue, oldValue) => {
        if (!newValue) {
            showConfirmDelete.value = false;
        }
        if (newValue) {
            form.clearErrors();
            form.name = props.unit.name;
            form.kaprodi_user_id = props.unit.kaprodi_user_id ?? '';
            form.unit_admins = props.unit.unit_admins.map(user => ({
                ...user,
                name: user.fullname, // Mengganti fullname dengan name
                fullname: undefined // Menghapus key fullname
            })) ?? [];
            form.stases = (props.unit.stases ?? []).map(stase => stase.id);
            updatedUserKaprodi.value = {
                id: props.unit.kaprodi_user?.id,
                name: props.unit.kaprodi_user?.fullname
            };
        }
    },
    { immediate: true }
);

const submit = () => {
    form.kaprodi_user_id = updatedUserKaprodi.value.id;
    form.put(route('units.update', { unit: props.unit }), {
        onSuccess: (data) => {
            form.clearErrors();
            showConfirmDelete.value = false;
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
    form.delete(route('units.destroy', { unit: props.unit }), {
        onSuccess: (data) => {
            form.reset();
            showConfirmDelete.value = false;
            emit('exitUpdate');
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
                    Update Unit
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

                        <SelectInput id="kaprodi_user_id" class="mt-1 block w-full" v-model="updatedUserKaprodi"
                            :options="kaprodiList" required autofocus autocomplete="kaprodi_user_id" />

                        <InputError class="mt-2" :message="form.errors.kaprodi_user_id" />
                    </div>
                    <div class="mt-2">
                        <InputLabel for="admin_user_id" value="Admin" />

                        <MultiselectBasic class="mt-1 block w-full" :key="form.name" :options="adminList"
                            v-model="form.unit_admins" />

                        <InputError class="mt-2" :message="form.errors.unit_admins" />
                    </div>
                    <div class="mt-2" v-if="$hasItems(stases)">
                        <InputLabel for="stases" value="Stases" />

                        <div class="ml-2 mt-1 block w-full">
                            <fieldset>
                                <legend class="sr-only">Stases</legend>
                                <InputError class="mt-2" :message="form.errors.stases" />
                                <div class="grid grid-cols-4 sm:grid-cols-3 lg:grid-cols-4 mt-2">
                                    <div class="space-y-5" v-for="stase in stases" :key="stase.title" :value="stase">
                                        <div class="relative flex items-start">
                                            <div class="flex h-6 items-center">
                                                <input :id="stase.name" :aria-describedby="stase.name + '-description'"
                                                    type="checkbox" :value="stase.id" v-model="form.stases"
                                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600" />
                                            </div>
                                            <div class="ml-3 text-sm leading-6">
                                                <label :for="stase.name" class="font-medium text-gray-900">{{ stase.name
                                                    }}</label>
                                                <p :id="stase.name + '-location'" class="text-gray-500">{{
                                                    stase.location }}
                                                </p>
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