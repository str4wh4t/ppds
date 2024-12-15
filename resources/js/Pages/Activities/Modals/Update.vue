<script setup>
import { ref, watch, nextTick } from 'vue';
import Modal from '@/Components/Modal.vue';

import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import TextArea from '@/Components/TextArea.vue';
import DatetimePicker from '@/Components/DatetimePicker.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInputBasic.vue';
import TimePicker from '@/Components/TimePicker.vue';
import moment from 'moment';

const emit = defineEmits(['exitUpdate']);
const props = defineProps({
    activity: Object,
    show: Boolean,
});

const showConfirmDelete = ref(false);

const form = useForm({
    name: '',
    type: '',
    date: '',
    start_time: '',
    finish_time: '',
    description: '',
    stase_id: '',
    location_id: '',
    dosen_user_id: '',
});

const activityOptions = usePage().props.constants.activity_types;
const staseOptions = usePage().props.stases;
const locationOptions = ref([]);
const dosen_list = usePage().props.dosen_list;

const dosenList = dosen_list.map(user => ({
    ...user,
    name: user.fullname, // Mengganti fullname dengan name
    fullname: undefined // Menghapus key fullname
}));
const dosenSelected = ref(null);

const activityType = ref(null);
const activityStase = ref(null);
const activityLocation = ref(null);

watch(
    () => props.show,
    (newValue) => {
        if (!newValue) {
            showConfirmDelete.value = false;
        }
        if (newValue) {
            form.clearErrors();
            form.name = props.activity.name;
            form.type = props.activity.type;
            activityType.value = props.activity.type;
            // activityStase.value = props.activity.unit_stase?.stase ? {
            //     id: props.activity.unit_stase?.stase.id,
            //     name: props.activity.unit_stase?.stase.name,
            //     label: props.activity.unit_stase?.stase.name + " - " + props.activity.unit_stase?.stase.stase_location.name
            // } : null;
            activityStase.value = props.activity.stase ?? null;
            activityLocation.value = props.activity.location ?? null;
            dosenSelected.value = {
                id: props.activity.dosen_user?.id,
                name: props.activity.dosen_user?.fullname
            };
            form.start_time = moment(props.activity.start_date).format('HH:mm');
            form.finish_time = moment(props.activity.end_date).format('HH:mm') === '00:00' ? '24:00' : moment(props.activity.end_date).format('HH:mm');
            form.description = props.activity.description ?? '';
            form.date = moment(props.activity.start_date).format('YYYY-MM-DD');  // cont : 2021-08-01
        }
    },
    { immediate: true }
);

const submit = () => {
    form.type = activityType.value.name ?? activityType.value;
    form.stase_id = activityStase.value?.id ?? null;
    form.location_id = activityLocation.value?.id ?? null;
    form.dosen_user_id = dosenSelected.value?.id ?? null;
    form.put(route('activities.update', { activity: props.activity }), {
        onSuccess: (data) => {
            form.clearErrors();
            showConfirmDelete.value = false;
        }
    })
};

const confirmDeleteDialog = ref(null);

const onShowConfirmDelete = () => {
    showConfirmDelete.value = true;
    nextTick(() => {
        confirmDeleteDialog.value.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

const deleteItem = () => {
    form.delete(route('activities.destroy', { activity: props.activity }), {
        onSuccess: (data) => {
            form.reset();
            showConfirmDelete.value = false;
            emit('exitUpdate');
        },
    });
}

const isLoading = ref(false);
const getAvailLocation = async (stase) => {
    try {
        isLoading.value = true;
        const responseData = await axios
            .get(route('stases.avail-location', { stase: stase }))
            .catch(error => {
                console.error('Error fetching users:', error);
            });
        return responseData;
    } catch (error) {
        console.error("Error fetching data:", error);
    } finally {
        //
        isLoading.value = false;
    }
}

watch(
    () => activityStase.value,
    async (newValue, oldValue) => {
        if (newValue !== null) {
            const response = await getAvailLocation(newValue);
            locationOptions.value = response.data.data;
        }
    }
);

const handleUpdateStase = (value) => {
    activityLocation.value = null;
}

</script>

<template>
    <Modal :show="show" @close="form.reset(), form.errors = {}">
        <div id="innerModal" class="divide-y divide-gray-200 overflow-hidden rounded-lg bg-white shadow">
            <div class="px-4 py-5 sm:px-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Update Activity
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

                    <div class="mt-2"
                        v-if="activityType &&
                            ((typeof activityType === 'string' && activityType.toLowerCase() === 'jaga') ||
                                (typeof activityType === 'object' && activityType.name && activityType.name.toLowerCase() === 'jaga'))">
                        <InputLabel for="stase" value="Stases" />
                        <SelectInput id="stase" class="mt-1 block w-full" v-model="activityStase"
                            @update:modelValue="handleUpdateStase" :options="staseOptions" required />
                        <InputError class="mt-2" :message="form.errors.stase_id" />
                    </div>

                    <div class="mt-2"
                        v-if="activityType &&
                            ((typeof activityType === 'string' && activityType.toLowerCase() === 'jaga') ||
                                (typeof activityType === 'object' && activityType.name && activityType.name.toLowerCase() === 'jaga'))">
                        <InputLabel for="location" value="Lokasi" />
                        <SelectInput id="location" class="mt-1 block w-full" v-model="activityLocation"
                            :options="locationOptions" required />
                        <InputError class="mt-2" :message="form.errors.location_id" />
                    </div>

                    <div class="mt-2">
                        <InputLabel for="dosen_user_id" value="Dosen" />
                        <SelectInput id="dosen_user_id" class="mt-1 block w-full" v-model="dosenSelected"
                            :options="dosenList" required />
                        <InputError class="mt-2" :message="form.errors.dosen_user_id" />
                    </div>

                    <div class="mt-2">
                        <InputLabel for="start_time" value="Mulai" />
                        <TimePicker id="start_time" idPrefix="start_time" class="mt-1 block w-full border-0"
                            v-model="form.start_time" required />
                        <InputError class="mt-2" :message="form.errors.start_time" />
                    </div>

                    <div class="mt-2">
                        <InputLabel for="finish_time" value="Selesai" />
                        <TimePicker id="finish_time" idPrefix="finish_time" class="mt-1 block w-full border-0"
                            v-model="form.finish_time" />
                        <InputError class="mt-2" :message="form.errors.finish_time" />
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