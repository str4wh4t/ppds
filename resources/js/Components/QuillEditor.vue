<template>
    <div class="col-span-full">
        <!-- Pratinjau Dokumen Jika Sudah Di-upload -->
        <div v-if="documentUrl" class="flex justify-center rounded-lg border border-gray-900/25 px-6 py-10">
            <div class="text-center">
                <p class="text-sm text-gray-600 mb-2">Pratinjau Dokumen yang Di-upload:</p>
                <!-- Link ke dokumen PDF -->
                <a :href="documentUrl" target="_blank" class="text-indigo-600 font-semibold hover:text-indigo-500">
                    Lihat Dokumen
                </a>
                <button @click="removeDocument" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-md">
                    Hapus Dokumen
                </button>
            </div>
        </div>

        <!-- Form Upload Jika Dokumen Belum Ada -->
        <div v-else class="flex justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10">
            <div class="text-center">
                <PhotoIcon class="mx-auto h-12 w-12 text-gray-300" aria-hidden="true" />
                <div class="mt-4 flex text-sm text-gray-600">
                    <label for="document"
                        class="relative cursor-pointer rounded-md bg-white font-semibold text-indigo-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-600 focus-within:ring-offset-2 hover:text-indigo-500">
                        <span>Upload a file</span>
                        <input id="document" name="document" type="file" class="sr-only" @change="handleFileUpload" />
                    </label>
                    <p class="pl-1">or drag and drop</p>
                </div>
                <p class="text-xs text-gray-600">PDF up to 10MB</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { PhotoIcon, UserCircleIcon } from '@heroicons/vue/24/solid'


// Emit events ke parent
const emit = defineEmits(['update:document', 'remove-document']);

// State untuk URL pratinjau
const documentUrl = ref(null);

// Fungsi untuk menangani upload file
const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (file && file.type === 'application/pdf' && file.size <= 10 * 1024 * 1024) {
        documentUrl.value = URL.createObjectURL(file); // Buat URL pratinjau dari Blob
        emit('update:document', file); // Emit file ke parent
    } else {
        alert('Please upload a valid PDF file (max 10MB).');
    }
};

// Fungsi untuk menghapus dokumen
const removeDocument = () => {
    // Hapus URL pratinjau
    if (documentUrl.value) {
        URL.revokeObjectURL(documentUrl.value); // Bersihkan URL yang dibuat
        documentUrl.value = null;
    }
    emit('remove-document');
    emit('update:document', null); // Bersihkan file di parent
};
</script>

<style scoped>
/* Optional styling */
</style>
