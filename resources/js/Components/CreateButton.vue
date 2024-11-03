<script setup>
import { PlusCircleIcon } from '@heroicons/vue/20/solid'
</script>
<template>
    <button type="button"
        class="inline-flex items-center gap-x-1.5 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
        <PlusCircleIcon class="-ml-0.5 h-4 w-4 u" aria-hidden="true" />
        <slot />
    </button>
</template>
