<template>
    <div>
        <div ref="editor" class="quill-editor"></div>
    </div>
</template>

<script>
import { ref, onMounted, watch, defineComponent } from 'vue';
import Quill from 'quill';

export default defineComponent({
    props: {
        modelValue: {
            type: String,
            default: '',
        },
    },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
        const editor = ref(null);
        let quill;

        onMounted(() => {
            // Inisialisasi Quill editor
            quill = new Quill(editor.value, {
                theme: 'snow',
                placeholder: 'Type your content here...',
                modules: {
                    toolbar: [
                        [{ header: [1, 2, false] }],
                        ['bold', 'italic', 'underline'],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        ['link', 'image'],
                    ],
                },
            });

            // Set initial content if there's any
            quill.root.innerHTML = props.modelValue;

            // Event listener untuk menangani perubahan di editor
            quill.on('text-change', () => {
                const html = quill.root.innerHTML;
                emit('update:modelValue', html);
            });
        });

        // Update content jika props berubah
        watch(() => props.modelValue, (newValue) => {
            if (quill.root.innerHTML !== newValue) {
                quill.root.innerHTML = newValue;
            }
        });

        return {
            editor,
        };
    },
});
</script>

<style>
@import 'quill/dist/quill.snow.css';

.quill-editor {
    min-height: 200px;
}
</style>
