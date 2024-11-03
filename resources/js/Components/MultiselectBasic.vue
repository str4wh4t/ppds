<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

// Props for the component
const props = defineProps({
    options: {
        type: Array,
        required: true,
        default: () => [],
    },
    modelValue: {
        type: Array,
        default: () => [],
    },
});

// Emitting events to parent
const emit = defineEmits(['update:modelValue', 'optionSelected']);

// Reactive data and methods
const searchTerm = ref('');
const isOpen = ref(false);
const selectedOptions = ref([...props.modelValue]);
const multiselectRef = ref(null);
const dropdownPosition = ref({});
const triggerButton = ref(null); // ref for the button
const dropdownMenu = ref(null); // ref for the dropdown menu

// Filtering options based on search term
const filteredOptions = computed(() => {
    return props.options.filter(
        (option) =>
            option.name.toLowerCase().includes(searchTerm.value.toLowerCase())
    );
});

// Toggle dropdown visibility
const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        const rect = triggerButton.value.getBoundingClientRect();
        dropdownPosition.value = {
            position: "absolute",
            top: `${rect.bottom + window.scrollY}px`,
            left: `${rect.left + window.scrollX}px`,
            width: `${rect.width}px` // Set width to match the button width
        };
    }
};

// Toggle selection of an option
const toggleOption = (option) => {
    const isSelected = selectedOptions.value.some(selected => selected.id === option.id);

    if (isSelected) {
        // Remove option if already selected
        selectedOptions.value = selectedOptions.value.filter(selected => selected.id !== option.id);
    } else {
        // Add option if not selected
        selectedOptions.value.push(option);
    }

    emit('update:modelValue', selectedOptions.value);
    // Emit 'optionSelected' event with the selected or unselected option
    emit('optionSelected', selectedOptions.value);
};

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    if (dropdownMenu.value &&
        triggerButton.value &&
        multiselectRef.value &&
        !dropdownMenu.value.contains(event.target) &&
        !triggerButton.value.contains(event.target) &&
        !multiselectRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

// Clear all selected options
const clearSelection = () => {
    selectedOptions.value = [];
    emit('update:modelValue', selectedOptions.value);
    emit('optionSelected', selectedOptions.value);
};


// Add and remove event listener for clicks outside component
onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

</script>

<template>
    <div class="relative w-full" ref="multiselectRef">
        <!-- Input Field to Show Selected Options and Trigger Dropdown -->
        <div ref="triggerButton"
            :class="['flex items-center bg-white border rounded-full px-3 py-2 shadow-sm cursor-pointer', isOpen ? 'border-green-600 ring-1 ring-green-600' : 'border-gray-300']"
            @click="toggleDropdown">
            <!-- Display Selected Options Text -->
            <span v-if="selectedOptions.length > 0" class="text-gray-700 text-sm truncate">
                {{ selectedOptions.map(option => option.name).join(', ') }}
            </span>
            <span v-else class="text-gray-400 text-sm">Select</span>

            <!-- Chevron Icon or Clear Icon -->
            <button v-if="selectedOptions.length > 0" @click.stop="clearSelection"
                class="ml-auto text-gray-400 hover:text-gray-600 focus:outline-none">
                <!-- Icon X -->
                <svg class="h-4 w-4 u" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <button v-else class="ml-auto text-gray-400 hover:text-gray-600 focus:outline-none">
                <!-- Chevron Icon -->
                <svg class="h-4 w-4 u" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                    aria-hidden="true">
                    <path fill-rule="evenodd"
                        d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z"
                        clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <!-- Dropdown List with Search and Checkbox Options -->
        <teleport to="body">
            <div v-if="isOpen" ref="dropdownMenu"
                class="absolute bg-white mt-1 ring-1 ring-gray-200 rounded-md shadow-lg z-50 max-h-48 overflow-y-auto z-50"
                :style="dropdownPosition">
                <!-- Search Input Inside Dropdown -->
                <div class="p-2 sticky top-0 bg-white z-10">
                    <input type="text" v-model="searchTerm" placeholder="Search..."
                        class="w-full px-2 py-2 border-0 border-gray-300 rounded-md text-gray-700 text-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-inset focus:ring-green-600" />
                </div>

                <!-- Checkbox Options -->
                <div v-for="option in filteredOptions" :key="option.id"
                    class="flex items-center p-2 hover:bg-blue-50 cursor-pointer text-sm">
                    <input type="checkbox"
                        class="form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        :checked="selectedOptions.some(selected => selected.id === option.id)"
                        @change="toggleOption(option)" />
                    <span class="ml-2 text-gray-700">{{ option.name }}</span>
                </div>
                <div v-if="filteredOptions.length === 0" class="p-2 text-gray-500 text-sm">No options found</div>
            </div>
        </teleport>
    </div>
</template>

<style scoped>
/* Styling to resemble PrimeVue multiselect */
.relative {
    min-width: 18rem;
}
</style>
