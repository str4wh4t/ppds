<template>
    <div class="bg-slate-500 p-2 w-full">
        <VuePdfEmbed :width="width" ref="pdfFrame" :source="source" />
    </div>
</template>

<script setup>
import { ref } from "vue";
import VuePdfEmbed from "vue-pdf-embed";

// Props
defineProps({
    source: {
        type: String,
        required: true, // Sumber file PDF
    },
    width: {
        type: [String, Number],
        default: "800", // Lebar default
    },
});

// Referensi DOM untuk VuePdfEmbed
const pdfFrame = ref(null);
</script>

<style scoped>
/* Tambahkan gaya tambahan di sini jika diperlukan */
.vue-pdf-embed {
    width: fit-content;
    margin: 0 auto;
    height: 600px;
    overflow-y: scroll;
}

.vue-pdf-embed__page {
    margin-bottom: 8px;
    box-shadow: 0 2px 8px 4px rgba(0, 0, 0, 0.1);
}
</style>