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
import SelectInput from '@/Components/SelectInputBasic.vue';

const props = defineProps({
    user: Object,
    show: Boolean,
});

const emit = defineEmits(['exitUpdate']);

const units = usePage().props.units;

const showConfirmDelete = ref(false);

const form = useForm({
    fullname: '',
    username: '',
    identity: '',
    student_unit_id: '',
    semester: '',
    dosbing_user_id: '',
    doswal_user_id: '',
    email: '',
});

const unit = ref(props.user?.student_unit);
const dosenPembimbing = ref({
    id: props.user?.dosbing_user?.id,
    name: props.user?.dosbing_user?.fullname
});

const dosenWali = ref({
    id: props.user?.doswal_user?.id,
    name: props.user?.doswal_user?.fullname
});

const dosen_list = usePage().props.dosen_list;
const dosenList = dosen_list.map(user => ({
    ...user,
    name: user?.fullname, // Mengganti fullname dengan name
    fullname: undefined // Menghapus key fullname
}));

watch(
    () => props.show,
    (newValue, oldValue) => {
        if (!newValue) {
            showConfirmDelete.value = false;
        }
        if (newValue) {
            form.clearErrors();
            form.fullname = props.user.fullname;
            form.username = props.user.username;
            form.identity = props.user.identity;
            form.student_unit_id = props.user?.student_unit_id ?? '';
            form.semester = props.user.semester;
            form.dosbing_user_id = props.user?.dosbing_user_id ?? '';
            form.doswal_user_id = props.user?.doswal_user_id ?? '';
            unit.value = props.user.student_unit;
            dosenPembimbing.value = {
                id: props.user.dosbing_user?.id,
                name: props.user.dosbing_user?.fullname
            };
            dosenWali.value = {
                id: props.user.doswal_user?.id,
                name: props.user.doswal_user?.fullname
            };
            form.email = props.user.email;
        }
    },
    { immediate: true }
);

const submit = () => {
    form.student_unit_id = unit.value?.id ?? null;
    form.dosbing_user_id = dosenPembimbing.value?.id ?? null;
    form.doswal_user_id = dosenWali.value?.id ?? null;
    form.put(route('students.update', { user: props.user.id }), {
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
    form.delete(route('users.destroy', { user: props.user.id }), {
        onSuccess: (data) => {
            form.reset();
            showConfirmDelete.value = false;
            emit('exitUpdate');
        },
    });
}

const onResetPassword = () => {
    form.patch(route('users.reset-password', { user: props.user.id }), {
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
                    Update Student
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
                        <InputLabel for="student_unit_id" value="Unit" />

                        <SelectInput id="student_unit_id" class="mt-1 block w-full" v-model="unit" :options="units"
                            required autofocus autocomplete="student_unit_id" />

                        <InputError class="mt-2" :message="form.errors.student_unit_id" />
                    </div>
                    <!-- <div class="mt-2">
                        <InputLabel for="semester" value="Semester" />

                        <TextInput id="semester" type="text" class="mt-1 block w-full" v-model="form.semester" required
                            autofocus autocomplete="semester" />

                        <InputError class="mt-2" :message="form.errors.semester" />
                    </div> -->
                    <div class="mt-2">
                        <InputLabel for="dosbing_user_id" value="Dosen Pembimbing" />

                        <SelectInput id="dosbing_user_id" class="mt-1 block w-full" v-model="dosenPembimbing"
                            :options="dosenList" required autofocus autocomplete="dosbing_user_id" />

                        <InputError class="mt-2" :message="form.errors.dosbing_user_id" />
                    </div>
                    <div class="mt-2">
                        <InputLabel for="doswal_user_id" value="Dosen Wali" />

                        <SelectInput id="doswal_user_id" class="mt-1 block w-full" v-model="dosenWali"
                            :options="dosenList" required autofocus autocomplete="doswal_user_id" />

                        <InputError class="mt-2" :message="form.errors.doswal_user_id" />
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