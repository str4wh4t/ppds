<template>
    <div class="flex flex-col items-start space-y-2">
        <div class="flex items-center space-x-2">
            <input :id="sanitizedIdPrefix + '-hour'" type="text" v-model="hour" @focus="clearPlaceholder('hour')"
                @keypress="onlyAllowNumbers" @blur="formatHourAndEmit" maxlength="2" class="w-12 px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-600
            text-center" placeholder="HH" />
            <span class="text-xl font-semibold">:</span>
            <input :id="sanitizedIdPrefix + '-minute'" type="text" v-model="minute" @focus="clearPlaceholder('minute')"
                @keypress="onlyAllowNumbers" @blur="formatMinuteAndEmit" maxlength="2" class="w-12 px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-600
            text-center" placeholder="MM" />
        </div>
    </div>
</template>

<script>
import { ref, watch, computed } from 'vue';

export default {
    name: 'TimePicker',
    props: {
        modelValue: {
            type: String,
            default: '00:00',
        },
        label: {
            type: String,
            default: 'Time',
        },
        idPrefix: {
            type: String,
            default: 'time-picker',
        },
    },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
        const sanitizedIdPrefix = computed(() => props.idPrefix.replace(/[^a-zA-Z0-9_-]/g, ''));

        const hour = ref(props.modelValue.split(':')[0] || '00');
        const minute = ref(props.modelValue.split(':')[1] || '00');

        // Watch untuk menyinkronkan nilai dari luar
        watch(
            () => props.modelValue,
            (newValue) => {
                hour.value = newValue.split(':')[0] || '00';
                minute.value = newValue.split(':')[1] || '00';
            },
            { immediate: true }
        );

        const onlyAllowNumbers = (event) => {
            const charCode = event.keyCode ? event.keyCode : event.which;
            if (charCode < 48 || charCode > 57) {
                event.preventDefault();
            }
        };

        const clearPlaceholder = (field) => {
            if (field === 'hour' && hour.value === '00') hour.value = '';
            if (field === 'minute' && minute.value === '00') minute.value = '';
        };

        const formatHourAndEmit = () => {
            // Format hour saat blur
            hour.value = hour.value.replace(/\D/g, '');
            if (hour.value === '' || hour.value < 0) hour.value = '00';
            if (hour.value > 24) hour.value = '24';
            hour.value = String(hour.value).padStart(2, '0');
            emitUpdatedTime();
        };

        const formatMinuteAndEmit = () => {
            // Format minute saat blur
            minute.value = minute.value.replace(/\D/g, '');
            if (minute.value === '' || minute.value < 0) minute.value = '00';
            if (minute.value > 59) minute.value = '59';
            minute.value = String(minute.value).padStart(2, '0');
            emitUpdatedTime();
        };

        const emitUpdatedTime = () => {
            const time = `${hour.value}:${minute.value}`;
            emit('update:modelValue', time);
        };

        return {
            hour,
            minute,
            sanitizedIdPrefix,
            formatHourAndEmit,
            formatMinuteAndEmit,
            onlyAllowNumbers,
            clearPlaceholder,
        };
    },
};
</script>

<style scoped>
input[type="text"]::-webkit-outer-spin-button,
input[type="text"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* input[type="text"] {
    -moz-appearance: textfield;
} */
</style>
