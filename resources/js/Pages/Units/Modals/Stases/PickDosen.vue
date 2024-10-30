<script setup>
import { ref, watch, nextTick, computed } from 'vue'
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
    stase: Object,
    show: Boolean,
});

const showConfirmDelete = ref(false);

const dosen_list = computed(() => {
    return (usePage().props.dosen_list ?? []).map(user => ({
        ...user,
        name: user.fullname, // Menambahkan atribut name yang berisi fullname
        fullname: undefined, // Menghapus fullname jika tidak diperlukan
    }));
});

// const updatedDosenUser = ref([]);
const form = useForm({
    name: '',
    stase_id: '',
    is_mandatory: false,
    dosen_stases: [],
});

watch(
    () => props.show,
    (newValue, oldValue) => {
        if (!newValue) {
            showConfirmDelete.value = false;
        }
        if (newValue) {
            form.name = props.stase.name;
            form.stase_id = props.stase.id;
            form.is_mandatory = props.stase.unit_stases[0].is_mandatory ? true : false;
            form.dosen_stases = (props.stase.unit_stases[0].users ?? []).map(user => ({
                ...user,
                name: user.fullname,
                fullname: undefined,
            }));
        }
    },
    { immediate: true }
);

const submit = () => {
    form.put(route('units.stases.update', { unit: usePage().props.unit }), {
        onSuccess: (data) => {
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

</script>
<template>
    <Modal :show="show" @close="form.reset(), form.errors = {}">
        <div id="innerModal" class="divide-y divide-gray-200 overflow-hidden rounded-lg bg-white shadow">
            <div class="px-4 py-5 sm:px-6">
                <!-- Content goes here -->
                <!-- We use less vertical padding on card headers on desktop than on body sections -->
                <h2 class="text-lg font-medium text-gray-900">
                    Update Unit Stase
                </h2>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <!-- Content goes here -->
                <form @submit.prevent="submit" class="mt-1 text-sm text-gray-600">
                    <div>
                        <InputLabel for="name" value="Name" />

                        <TextInput id="name" type="text" class="mt-1 block w-full" readonly v-model="form.name" required
                            autofocus autocomplete="name" />

                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                    <div class="mt-2">
                        <InputLabel for="dosen_user_id" value="Dosen" />

                        <MultiselectBasic id="dosen_user_id" class="mt-1 block w-full" v-model="form.dosen_stases"
                            :options="dosen_list" :key="form.name" autocomplete="dosen_user_id" />

                        <InputError class="mt-2" :message="form.errors.dosen_user_id" />
                    </div>
                    <div class="mt-2">
                        <InputLabel for="stases" value="Mandatory" />
                        <div class="ml-2 mt-1 block w-full">
                            <fieldset>
                                <legend class="sr-only">Mandatory</legend>
                                <InputError class="mt-2" :message="form.errors.stases" />
                                <div class="mt-2">
                                    <div class="space-y-5">
                                        <div class="relative flex items-start">
                                            <div class="flex h-6 items-center">
                                                <input :id="form.name" :aria-describedby="form.name + '-description'"
                                                    type="checkbox" v-model="form.is_mandatory"
                                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600" />
                                            </div>
                                            <div class="ml-3 text-sm leading-6">
                                                <label :for="form.name" class="font-medium text-gray-900">is
                                                    Mandatory ?</label>
                                                <p class="text-gray-500">Stase wajib harus diambil</p>
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