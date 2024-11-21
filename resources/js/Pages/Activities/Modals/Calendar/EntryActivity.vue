<script setup>
import {
    ref, watch, nextTick
} from 'vue'
import Modal from '@/Components/Modal.vue';

import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import TextArea from '@/Components/TextArea.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import SelectInput from '@/Components/SelectInputBasic.vue';
import TimePicker from '@/Components/TimePicker.vue';
import CreateButton from '@/Components/CreateButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import moment from 'moment';
import { ClockIcon, PencilSquareIcon, PlusCircleIcon } from '@heroicons/vue/24/outline';
import DangerButton from '@/Components/DangerButton.vue';
import QuillEditor from '@/Components/QuillEditor.vue';

const props = defineProps({
    show: Boolean,
    selectedDay: Object,
    activities: Array,
});

const activityOptions = usePage().props.constants.activity_types;
const staseOptions = usePage().props.stases;

const emit = defineEmits(['updateProps']);

const isFormShow = ref(false);
const showConfirmDelete = ref(false);
const activityType = ref(null);
const activityStase = ref(null);
const selectedActivity = ref(null);
const confirmDeleteDialog = ref(null);
const btnConfirmDelete = ref(false);
const isUpdate = ref(false);

const form = useForm({
    name: '',
    type: '',
    date: '',
    start_time: '',
    finish_time: '',
    description: '',
    stase_id: '',
});

watch(
    () => props.show,
    (newValue, oldValue) => {
        if (newValue) {
            form.reset();
            form.clearErrors();
            activityType.value = null;
            activityStase.value = null;
            showConfirmDelete.value = false;
            btnConfirmDelete.value = false;
            isUpdate.value = false;
            isFormShow.value = false;
        }
    },
    { immediate: true }
);

watch(
    () => isFormShow.value,
    (newValue, oldValue) => {
        if (!newValue) {
            form.reset();
            form.clearErrors();
            activityType.value = null;
            activityStase.value = null;
            showConfirmDelete.value = false;
            btnConfirmDelete.value = false;
            isUpdate.value = false;
        }
    },
    { immediate: true }
);

const openForm = () => {
    form.reset();
    isFormShow.value = true;
}

const openUpdate = (activity) => {
    isFormShow.value = true;
    isUpdate.value = true;
    form.name = activity.name;
    form.type = activity.type;
    activityType.value = activity.type;
    activityStase.value = activity.unit_stase?.stase ? {
        id: activity.unit_stase?.stase.id,
        name: activity.unit_stase?.stase.name,
        label: activity.unit_stase?.stase.name + " - " + activity.unit_stase?.stase.stase_location.name
    } : null;
    form.start_time = moment(activity.start_date).format('HH:mm');
    form.finish_time = moment(activity.end_date).format('HH:mm') === '00:00' ? '24:00' : moment(activity.end_date).format('HH:mm');
    // form.start_time = mmDate({ date: activity.start_date, formatOutput: 'HH:mm' });
    // form.finish_time = mmDate({ date: activity.end_date, formatOutput: 'HH:mm' });
    form.description = activity.description;
    btnConfirmDelete.value = true;
    selectedActivity.value = activity;
}

const submit = () => {
    form.type = activityType.value?.name ?? activityType.value;
    form.stase_id = activityStase.value?.id ?? null;
    form.date = props.selectedDay.date;
    if (isUpdate.value === false) {
        form.post(route('activities.store'), {
            onSuccess: (data) => {
                form.reset();
                activityType.value = null;
                activityStase.value = null;
                emit('updateProps', data.props, props.selectedDay);
            },
        });

    } else {
        form.put(route('activities.update', { activity: selectedActivity.value }), {
            onSuccess: (data) => {
                form.reset();
                showConfirmDelete.value = false;
                emit('updateProps', data.props, props.selectedDay);
                isFormShow.value = false;
            }
        })
    }
};

const onShowConfirmDelete = () => {
    showConfirmDelete.value = true;
    nextTick(() => {
        confirmDeleteDialog.value.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

const deleteItem = () => {
    form.delete(route('activities.destroy', { activity: selectedActivity.value }), {
        onSuccess: (data) => {
            isFormShow.value = false;
            emit('updateProps', data.props, props.selectedDay);
        },
    });
}

const totalWorkload = (activities) => {
    let totalMilliseconds = 0;

    activities.forEach(activity => {
        const start = new Date(activity.start_date);
        const end = new Date(activity.end_date);

        // Selisih waktu dalam milidetik
        const duration = end - start;
        totalMilliseconds += duration;
    });

    // Konversi total milidetik ke total menit
    const totalMinutes = Math.floor(totalMilliseconds / (1000 * 60));

    // Menghitung jam dan menit
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;

    // Format ke HH:mm
    const formattedDuration = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;

    return formattedDuration;
};

</script>

<template>
    <Modal :show="show" @close="form.reset(), form.errors = {}">
        <div class="divide-y divide-gray-200 overflow-hidden rounded-lg bg-white shadow">
            <div class="px-4 py-5 sm:px-6 flex">
                <h2 class="text-lg flex-auto font-medium text-gray-900">
                    Buat Logbook
                </h2>
                <h2 class="text-lg flex-none font-medium text-gray-900 font-bold text-indigo-600">
                    {{ $formatDate({ date: selectedDay.date }) }}
                </h2>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <div v-show="!isFormShow">
                    <div class="flex">
                        <div class="flex-auto">
                            <time :datetime="selectedDay.date" class="mt-1 mb-1 flex items-center text-gray-700">
                                Total Jam Hari :
                                <span class="ml-1 font-bold text-indigo-600">{{ totalWorkload(activities) }}</span>
                            </time>
                        </div>
                        <div class="flex-none">
                            <time :datetime="selectedDay.date" class="mt-1 mb-1 flex items-right text-gray-700">
                                Total Jam Minggu :
                                <span class="ml-1 font-bold text-indigo-600">{{ selectedDay.workload }}</span>
                            </time>
                        </div>
                    </div>

                    <div class="mt-1 ring-1 ring-gray-300 sm:mx-0 sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead>
                                <tr>
                                    <th scope="col"
                                        class="py-2 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                                        Aktifitas
                                    </th>
                                    <th scope="col"
                                        class="hidden px-3 py-2 text-left text-sm font-semibold text-gray-900 lg:table-cell">
                                        Jenis</th>
                                    <th scope="col" class="px-3 py-2 text-sm font-semibold text-gray-900 lg:table-cell">
                                        Waktu</th>
                                    <th scope="col"
                                        class="hidden px-3 py-2 text-left text-sm font-semibold text-gray-900 lg:table-cell">
                                        Deskripsi</th>
                                    <th scope="col" class="relative py-1 pl-3 pr-4 sm:pr-6">
                                        &nbsp;
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="$hasItems(activities)" v-for="(activity, index) in activities"
                                    :key="activity.id">
                                    <td
                                        :class="[index === 0 ? '' : 'border-t border-transparent', 'relative py-1 pl-4 pr-3 text-sm sm:pl-6']">
                                        <div class="font-medium text-gray-900">
                                            {{ activity.name }}
                                        </div>
                                        <div class="mt-1 flex flex-col text-gray-500 sm:block lg:hidden">
                                            <span>{{ activity.type }} / {{ activity.unit_stase?.stase.name ?? '' }}
                                            </span>
                                        </div>
                                        <div v-if="index !== 0"
                                            class="absolute -top-px left-6 right-0 h-px bg-gray-200" />
                                    </td>
                                    <td
                                        :class="[index === 0 ? '' : 'border-t border-gray-200', 'hidden px-3 py-2 text-sm text-gray-500 lg:table-cell']">
                                        <div class="font-medium text-gray-900">
                                            {{ activity.type }}
                                        </div>
                                        <div v-if="activity.type == 'jaga'"
                                            class="hidden mt-1 flex flex-col text-gray-500 sm:block">
                                            <span class="sm:block">{{ activity.unit_stase.stase.name }}</span>
                                            <span>{{ activity.unit_stase.stase.stase_location.name }}</span>
                                        </div>
                                        <div v-if="index !== 0"
                                            class="absolute -top-px left-6 right-0 h-px bg-gray-200" />
                                    </td>
                                    <td
                                        :class="[index === 0 ? '' : 'border-t border-gray-200', 'px-3 py-2 text-sm text-center text-gray-500 lg:table-cell']">
                                        {{ $formatDate({ date: activity.start_date, formatOutput: 'HH:mm' }) }} - {{
                                            $formatDate({ date: activity.end_date, formatOutput: 'HH:mm' }) }}</td>
                                    <td
                                        :class="[index === 0 ? '' : 'border-t border-gray-200', 'hidden px-3 py-2 text-sm text-gray-500 lg:table-cell']">
                                        {{ $truncatedText(activity.description) }}</td>
                                    <td
                                        :class="[index === 0 ? '' : 'border-t border-transparent', 'relative py-1 pl-3 pr-4 text-center text-sm font-medium sm:pr-6']">
                                        <button type="button" @click="openUpdate(activity)"
                                            class="inline-flex items-center rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-white">
                                            <PencilSquareIcon class="h-4 w-4 u" aria-hidden="true" />
                                        </button>
                                        <div v-if="index !== 0"
                                            class="absolute -top-px left-0 right-6 h-px bg-gray-200" />
                                    </td>
                                </tr>
                                <tr v-else>
                                    <td colspan="5" class="h-10 text-center font-bold text-indigo-600">Empty</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="w-full text-center justify-center mt-5">
                        <button @click="openForm" type="button"
                            class="inline-flex items-center justify-center gap-x-1.5 rounded-full bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            <PlusCircleIcon class="-ml-0.5 h-4 w-4 u" aria-hidden="true" />
                            Tambah aktifitas
                        </button>
                    </div>
                </div>
                <form @submit.prevent="submit" v-show="isFormShow" class="mt-1 text-sm text-gray-600">

                    <div>
                        <InputLabel for="name" value="Aktifitas" />
                        <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required
                            autofocus autocomplete="name" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="mt-2">
                        <InputLabel for="type" value="Jenis" />
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
                            :options="staseOptions" required />
                        <InputError class="mt-2" :message="form.errors.stase_id" />
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
                        <InputLabel for="description" value="Deskripsi" />
                        <TextArea id="description" class="mt-1 block w-full" required v-model="form.description" />
                        <!-- <QuillEditor class="mt-1 block w-full" v-model="form.description" /> -->
                        <InputError class="mt-2" :message="form.errors.description" />
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                            <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
                        </Transition>
                        <SecondaryButton class="ml-2" :disabled="form.processing" @click="isFormShow = false">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton class="ml-4" :disabled="form.processing">
                            Save
                        </PrimaryButton>
                        <DangerButton v-if="btnConfirmDelete" type="button" class="ml-2" :disabled="form.processing"
                            @click="onShowConfirmDelete">
                            Delete
                        </DangerButton>
                    </div>
                    <div v-if="showConfirmDelete" ref="confirmDeleteDialog" tabindex="-1"
                        class="flex flex-col items-center justify-center mt-4 border-2 border-yellow-400 bg-yellow-50 p-4 text-center">
                        <div>
                            Yakin akan menghapus data ini?
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