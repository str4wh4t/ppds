<script setup>
import { ref, watch } from 'vue'
import Modal from '@/Components/Modal.vue';

import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import TextArea from '@/Components/TextArea.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import SelectInput from '@/Components/SelectInputBasic.vue';
import DatetimePicker from '@/Components/DatetimePicker.vue';

const props = defineProps({
    show: Boolean,
});

const activityOptions = usePage().props.constants.activity_types;

const form = useForm({
    name: '',
    type: '',
    start_date: '',
    end_date: '',
    description: '',
});

const activityType = ref(null);

watch(
    () => props.show,
    (newValue, oldValue) => {
        if (newValue) {
            form.reset();
            form.clearErrors();
            activityType.value = null;
        }
    },
    { immediate: true }
);

const submit = () => {
    form.type = activityType.value?.name ?? '';
    form.post(route('activities.store'), { // Sesuaikan dengan route yang benar
        onSuccess: (data) => {
            form.reset();
            form.clearErrors();
            activityType.value = null;
        },
    });
};

</script>

<template>
    <Modal :show="show" @close="form.reset(), form.errors = {}">
        <div class="divide-y divide-gray-200 overflow-hidden rounded-lg bg-white shadow">
            <div class="px-4 py-5 sm:px-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Create Logbook
                </h2>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <form @submit.prevent="submit" class="mt-1 text-sm text-gray-600">

                    <div>
                        <InputLabel for="name" value="Activity Name" />
                        <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required
                            autofocus autocomplete="name" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="mt-2">
                        <InputLabel for="type" value="Activity Type" />
                        <SelectInput id="type" class="mt-1 block w-full" v-model="activityType"
                            :options="activityOptions" required />
                        <InputError class="mt-2" :message="form.errors.type" />
                    </div>

                    <div class="mt-2">
                        <InputLabel for="start_date" value="Start Date" />
                        <DatetimePicker id="start_date" type="date" class="mt-1 block w-full border-0"
                            v-model="form.start_date" required />
                        <InputError class="mt-2" :message="form.errors.start_date" />
                    </div>

                    <div class="mt-2">
                        <InputLabel for="end_date" value="End Date" />
                        <DatetimePicker id="end_date" type="date" class="mt-1 block w-full border-0"
                            v-model="form.end_date" />
                        <InputError class="mt-2" :message="form.errors.end_date" />
                    </div>

                    <div class="mt-2">
                        <InputLabel for="description" value="Description" />
                        <TextArea id="description" class="mt-1 block w-full" required v-model="form.description" />
                        <InputError class="mt-2" :message="form.errors.description" />
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
