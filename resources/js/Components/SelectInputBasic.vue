<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    options: {
        type: Array,
        required: true
    },
    modelValue: {
        type: [Object, String],
        default: null
    },
    placeholder: {
        type: String,
        default: 'Select'
    }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
// const selectedItem = ref(props.modelValue); // Initialize with modelValue
const searchQuery = ref("");
const dropdownPosition = ref({});
const triggerButton = ref(null); // ref for the button
const dropdownMenu = ref(null); // ref for the dropdown menu

// Watch for changes in modelValue to update selectedItem
watch(
    () => props.modelValue,
    (newValue) => {
        if (typeof newValue === 'string') {

            selectedItem.value = findItemByName(newValue);
        } else {
            selectedItem.value = newValue;
        }
    }
);

// Normalisasi options menjadi objek dengan id dan name
const normalizedOptions = computed(() => {
    return props.options.map((item, index) => {
        if (typeof item === 'object' && item !== null && item.hasOwnProperty('id') && item.hasOwnProperty('name')) {
            return item;
        }

        return {
            id: (index + 1).toString(), // gunakan index + 1 sebagai id unik dalam bentuk string
            name: String(item)
        };
    });
});

// Computed property to filter options based on search query
// Filtered items berdasarkan searchQuery (sesuai contoh sebelumnya)
const filteredItems = computed(() => {
    return normalizedOptions.value.filter(item =>
        item.hasOwnProperty('label') ? item.label.toLowerCase().includes(searchQuery.value.toLowerCase()) : item.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

// Inisialisasi selectedItem dengan mengecek modelValue
const selectedItem = ref(
    typeof props.modelValue === 'string'
        ? findItemByName(props.modelValue) // Jika modelValue adalah string, temukan item dengan nama yang cocok
        : props.modelValue
);

// Fungsi untuk menemukan item dalam filteredItems berdasarkan name
function findItemByName(name) {
    const item = filteredItems.value.find(item => item.name === name);
    return item ? (item.hasOwnProperty('label') ? { id: item.id, name: item.name, label: item.label } : { id: item.id, name: item.name }) : { id: null, name };
}

const toggleDropdown = async () => {
    isOpen.value = !isOpen.value;
    await nextTick();
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

const selectItem = (item) => {
    selectedItem.value = item;
    emit('update:modelValue', item); // Emit to parent for v-model sync
    isOpen.value = false;
    searchQuery.value = ""; // Clear search after selection
};

// Function to close dropdown when clicking outside
const handleClickOutside = (event) => {
    if (
        dropdownMenu.value &&
        triggerButton.value &&
        !dropdownMenu.value.contains(event.target) &&
        !triggerButton.value.contains(event.target)
    ) {
        isOpen.value = false;
    }
};

const clearSelection = () => {
    selectedItem.value = null;
    emit('update:modelValue', null); // Emit null untuk sinkronisasi dengan v-model
    searchQuery.value = ""; // Kosongkan input pencarian
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
});
</script>

<template>
    <div class="relative w-full">
        <!-- Trigger Dropdown -->
        <div ref="triggerButton" @click="toggleDropdown" type="button"
            :class="[selectedItem ? [selectedItem.name ? 'text-gray-700' : 'text-gray-400'] : 'text-gray-400', isOpen ? 'border-green-600 ring-1 ring-green-600' : 'border-gray-300', 'w-full flex items-center cursor-pointer justify-between px-4 py-2 border rounded-full bg-white shadow-sm text-left text-sm']">
            <span>{{ selectedItem ? (selectedItem.hasOwnProperty('label') ? selectedItem.label : (selectedItem.name ??
                placeholder)) : placeholder }}</span>
            <!-- Change caret to X when an item is selected -->
            <button v-if="selectedItem" @click.stop="clearSelection"
                class="text-gray-500 hover:text-red-500 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <svg v-else class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.293l3.71-4.07a.75.75.75 0 01-.02 1.06l-4 4.25a.75.75.75 0 01-1.06 0l-4-4.25a.75.75.75 0 01-.02-1.06z"
                    clip-rule="evenodd" />
            </svg>
        </div>

        <!-- Dropdown with Teleport -->
        <teleport to="body">
            <div v-if="isOpen" ref="dropdownMenu"
                class="absolute bg-white mt-1 ring-1 ring-gray-200 rounded-md shadow-lg z-50 max-h-48 overflow-y-auto z-50"
                :style="dropdownPosition">
                <!-- Search Input -->
                <div class="p-2">
                    <input type="text" v-model="searchQuery" placeholder="Search..."
                        class="w-full px-3 py-2 border-0 border-gray-300 rounded-md text-gray-700 text-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:outline-none focus:border-green-600 focus:ring-inset focus:ring-green-600" />
                </div>

                <!-- Item List -->
                <ul class="cursor-pointer text-sm text-gray-700">
                    <li v-for="item in filteredItems" :key="item.id" @click="selectItem(item)"
                        class="p-2 hover:bg-blue-50 cursor-pointer"
                        :class="{ 'bg-indigo-100': item.id === selectedItem?.id }">
                        <span>{{ item.hasOwnProperty('label') ? item.label : item.name }}</span>
                    </li>
                </ul>
            </div>
        </teleport>
    </div>
</template>

<style scoped>
/* Custom styling for scrollbar (optional) */
.max-h-60::-webkit-scrollbar {
    width: 8px;
}

.max-h-60::-webkit-scrollbar-thumb {
    background-color: #cbd5e0;
    border-radius: 4px;
}
</style>
