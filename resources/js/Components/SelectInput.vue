<script setup>
import { ref } from 'vue'
import { Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions } from '@headlessui/vue'
import { CheckIcon, ChevronDownIcon } from '@heroicons/vue/20/solid'

// Mendefinisikan prop
const props = defineProps({
    selectOptions: Object // atau tipe lain seperti Number, Array, Object, dll.
});

const selected = ref(props.selectOptions[0])
</script>
<template>
    <Listbox as="div" v-model="selected">
        <div class="relative">
            <div class="inline-flex divide-x divide-indigo-700 rounded-md shadow-sm">
                <div
                    class="inline-flex items-center gap-x-1.5 rounded-l-md bg-indigo-600 px-3 py-2 text-white shadow-sm">
                    <CheckIcon class="-ml-0.5 h-4 w-4 u" aria-hidden="true" />
                    <p class="text-sm font-semibold">{{ selected.label }}</p>
                </div>
                <ListboxButton
                    class="inline-flex items-center rounded-l-none rounded-r-md bg-indigo-600 p-2 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 focus:ring-offset-gray-50">
                    <span class="sr-only">Change status</span>
                    <ChevronDownIcon class="h-4 w-4 u text-white" aria-hidden="true" />
                </ListboxButton>
            </div>

            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100"
                leave-to-class="opacity-0">
                <ListboxOptions
                    class="z-10 mt-2 w-72 origin-top-right divide-y divide-gray-200 overflow-hidden rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                    <ListboxOption as="template" v-for="option in selectOptions" :key="option.id" :value="option"
                        v-slot="{ active, selected }">
                        <li
                            :class="[active ? 'bg-indigo-600 text-white' : 'text-gray-900', 'cursor-default select-none p-4 text-sm']">
                            <div class="flex flex-col">
                                <div class="flex justify-between">
                                    <p :class="selected ? 'font-semibold' : 'font-normal'">{{ option.label }}</p>
                                    <span v-if="selected" :class="active ? 'text-white' : 'text-indigo-600'">
                                        <CheckIcon class="h-4 w-4 u" aria-hidden="true" />
                                    </span>
                                </div>
                                <p :class="[active ? 'text-indigo-200' : 'text-gray-500', 'mt-2']">{{ option.description
                                    }}</p>
                            </div>
                        </li>
                    </ListboxOption>
                </ListboxOptions>
            </transition>
        </div>
    </Listbox>
</template>