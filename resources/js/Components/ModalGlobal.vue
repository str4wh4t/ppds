<!-- resources/js/Components/ModalGLobal.vue -->
<script setup>
import { useModalStore } from '@/stores/modalStore';
import { ref } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { CheckIcon } from '@heroicons/vue/24/outline'
import Modal from '@/Components/Modal.vue';

// const open = ref(true)

const modalStore = useModalStore(); // Mengakses store modal dari Pinia
</script>

<template>
    <Modal :show="modalStore.isOpen" @close="modalStore.closeModal">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                {{ modalStore.title }}
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                {{ modalStore.message }}
            </p>
        </div>
    </Modal>
</template>
