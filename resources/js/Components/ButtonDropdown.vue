<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { ChevronDownIcon } from '@heroicons/vue/20/solid'

// const items = [
//     { name: 'Save and schedule', href: '#' },
//     { name: 'Save and publish', href: '#' },
//     { name: 'Export PDF', href: '#' },
// ]

const props = defineProps({
    items: Object,
});
</script>
<template>
    <div class="inline-flex rounded-md shadow-sm">
        <button type="button"
            class="relative inline-flex items-center rounded-l-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-10">Options</button>
        <Menu as="div" class="relative -ml-px block">
            <MenuButton
                class="relative inline-flex items-center rounded-r-md bg-white px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-10">
                <span class="sr-only">Options</span>
                <ChevronDownIcon class="h-4 w-4 u" aria-hidden="true" />
            </MenuButton>
            <transition enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95">
                <MenuItems
                    class="absolute right-0 z-10 -mr-1 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                    <div class="py-1">
                        <MenuItem v-for="item in items" :key="item.name" v-slot="{ active }">
                        <a :href="item.href" @click="item.action"
                            :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block px-4 py-2 text-sm text-left']">{{
                                item.name }}</a>
                        </MenuItem>
                    </div>
                </MenuItems>
            </transition>
        </Menu>
    </div>
</template>