<template>
    <div class="col-span-full">
        <!-- Pratinjau Dokumen Jika Sudah Di-upload -->
        <div v-if="documentUrl || initialDocumentUrl"
            class="flex justify-center rounded-lg border border-dashed border-gray-900/25 px-5 py-5">
            <div class="text-center w-full">
                <!-- Pratinjau PDF dalam iframe -->
                <!-- <button @click="removeDocument" class="mb-4 px-4 py-2 bg-red-600 text-white rounded-md">
                    Hapus Dokumen
                </button> -->
                <iframe :src="documentUrl || initialDocumentUrl" :type="validTypes[fileType]"
                    class="w-full h-64 border rounded-md" frameborder="0">
                </iframe>
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
                <p class="text-xs text-gray-600">.{{ fileType }} file up to 10MB</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { PhotoIcon } from '@heroicons/vue/24/outline';

// Menggunakan `initialDocumentPath` untuk pratinjau dokumen awal dari server
const props = defineProps({
    modelValue: File,
    initialDocumentPath: String, // Path dokumen awal dari server
    fileType: {
        type: String,
        default: 'pdf'
    }
});
const emit = defineEmits(['update:modelValue']);

const documentUrl = ref(null);
const initialDocumentUrl = ref(null);

// Inisialisasi URL awal dengan path dari server
if (props.initialDocumentPath) {
    initialDocumentUrl.value = `/storage/${props.initialDocumentPath}`;
}

// Watch untuk perubahan modelValue, membuat pratinjau jika ada file baru
watch(() => props.modelValue, (newFile) => {
    if (newFile) {
        documentUrl.value = URL.createObjectURL(newFile);
    } else {
        documentUrl.value = null;
    }
});
const validTypes = {
    pdf: 'application/pdf',
    jpeg: 'image/jpeg'
};

// Fungsi untuk menangani upload file baru
const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (file && file.type === validTypes[props.fileType] && file.size <= 10 * 1024 * 1024) {
        documentUrl.value = URL.createObjectURL(file); // Membuat URL pratinjau dari Blob baru
        emit('update:modelValue', file); // Update modelValue (file baru)
        initialDocumentUrl.value = null; // Hilangkan pratinjau lama jika ada file baru
    } else {
        alert(`Please upload a valid ${props.fileType.toUpperCase()} file (max 10MB).`);
    }
};

const removeDocument = () => {
    if (documentUrl.value) {
        URL.revokeObjectURL(documentUrl.value); // Bersihkan URL sementara
        documentUrl.value = null;
    }
    initialDocumentUrl.value = null; // Hapus pratinjau lama
    emit('update:modelValue', null); // Bersihkan file di parent
};

// Expose fungsi removeDocument ke parent
defineExpose({
    removeDocument
});

</script>

<style>
iframe {
    width: 100%;
    height: 500px;
    /* Sesuaikan tinggi sesuai kebutuhan */
}
</style>
