<script setup>
import { ref, watch } from 'vue'
import Modal from '@/Components/Modal.vue';

import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import SelectInput from '@/Components/SelectInputBasic.vue';

const props = defineProps({
    show: Boolean,
});

const units = usePage().props.units;
const unit = ref(null);

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

const dosen_list = usePage().props.dosen_list;
const dosenList = dosen_list.map(user => ({
    ...user,
    name: user?.fullname, // Mengganti fullname dengan name
    fullname: undefined // Menghapus key fullname
}));
const dosenPembimbing = ref(null);
const dosenWali = ref(null);

watch(
    () => props.show,
    (newValue, oldValue) => {
        if (newValue) {
            form.reset();
            form.clearErrors();
            unit.value = null;
            dosenPembimbing.value = null;
            dosenWali.value = null;
        }
    },
    { immediate: true }
);

const submit = () => {
    form.student_unit_id = unit.value?.id ?? null;
    form.dosbing_user_id = dosenPembimbing.value?.id ?? null;
    form.doswal_user_id = dosenWali.value?.id ?? null;
    form.post(route('students.store'), {
        onSuccess: (data) => {
            form.reset();
            form.clearErrors();
            unit.value = null;
            dosenPembimbing.value = null;
            dosenWali.value = null;
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
                    Create Student
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
                    <!-- <div class="mt-2">
                        <InputLabel for="semester" value="Semester" />

                        <TextInput id="semester" type="text" class="mt-1 block w-full" v-model="form.semester" required
                            autofocus autocomplete="semester" />

                        <InputError class="mt-2" :message="form.errors.semester" />
                    </div> -->
                    <div class="mt-2">
                        <InputLabel for="student_unit_id" value="Unit" />

                        <SelectInput id="student_unit_id" class="mt-1 block w-full" v-model="unit" :options="units"
                            required autofocus autocomplete="student_unit_id" />

                        <InputError class="mt-2" :message="form.errors.student_unit_id" />
                    </div>
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
                    </div>
                </form>
            </div>
        </div>
    </Modal>
</template>