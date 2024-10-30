<template>
    <div class="datetime-picker">
        <label :for="id" class="block text-sm font-medium text-gray-700">{{ label }}</label>
        <div class="mt-1 flex">
            <input :id="id + '-date'" type="date" v-model="date"
                class="date-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                @input="updateValue" required />
            <input :id="id + '-time'" type="time" v-model="time"
                class="time-input mt-1 block w-full ml-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                @input="updateValue" required />
        </div>
        <InputError v-if="error" :message="error" />
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    modelValue: String,
    label: String,
    id: String,
    error: String,
});

const emit = defineEmits(['update:modelValue']);

const date = ref('');
const time = ref('');

// Perbarui date dan time setiap kali modelValue berubah
watch(
    () => props.modelValue,
    (newVal) => {
        if (newVal) {
            const [d, t] = newVal.split(' ');
            date.value = d || '';
            time.value = t || '';
        } else {
            date.value = '';
            time.value = '';
        }
    },
    { immediate: true }
);

const updateValue = () => {
    const dateTime = `${date.value} ${time.value}`;
    emit('update:modelValue', dateTime.trim());
};
</script>

<style scoped>
.datetime-picker {
    display: flex;
    flex-direction: column;
}

.date-input,
.time-input {
    width: calc(50% - 0.5rem);
}
</style>
