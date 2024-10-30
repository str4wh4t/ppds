// resources/js/stores/modalStore.js
import { defineStore } from "pinia";
import { ref } from "vue";

export const useModalStore = defineStore("modal", () => {
  const isOpen = ref(false);
  const title = ref("");
  const message = ref("");

  function openModal(newTitle, newMessage) {
    title.value = newTitle;
    message.value = newMessage;
    isOpen.value = true;
  }

  function closeModal() {
    isOpen.value = false;
    title.value = "";
    message.value = "";
  }

  return { isOpen, title, message, openModal, closeModal };
});
