<template>
    <div class="mb-4">
        <label v-if="label" :for="id" class="block text-sm font-medium text-gray-700">
            {{ label }}
        </label>
        <div class="mt-1">
            <textarea :id="id" :name="name" :placeholder="placeholder" :rows="rows" v-model="inputValue"
                @input="$emit('update:modelValue', inputValue)"
                :class="['shadow-sm block w-full sm:text-sm border rounded-md', error ? 'border-red-500' : 'border-gray-300']"></textarea>
            <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

// Props untuk komponen
const props = defineProps({
    modelValue: String,
    label: String,
    placeholder: String,
    rows: {
        type: Number,
        default: 4,
    },
    error: String,
    id: {
        type: String,
        default: () => `textarea-${Math.random().toString(36).substr(2, 9)}`,
    },
    name: String,
});

// Menggunakan v-model untuk binding dua arah
const inputValue = ref(props.modelValue);

// Menyinkronkan nilai prop `modelValue` jika berubah dari luar
watch(() => props.modelValue, (newValue) => {
    inputValue.value = newValue;
});
</script>

<style scoped>
/* Tambahan styling jika diperlukan */
</style>