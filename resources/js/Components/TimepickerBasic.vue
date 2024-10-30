<template>
    <div class="datetime-picker">
        <label :for="id" class="block text-sm font-medium text-gray-700">{{ label }}</label>
        <div class="mt-1 flex">
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

const time = ref('');

// Perbarui time setiap kali modelValue berubah
watch(
    () => props.modelValue,
    (newVal) => {
        if (newVal) {
            time.value = newVal || '';
        } else {
            time.value = '';
        }
    },
    { immediate: true }
);

const updateValue = () => {
    const theTime = `${time.value}`;
    emit('update:modelValue', theTime.trim());
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
