<template>
    <Link :href="route('notifs.index')" method="get" as="button" :class="{
        '-m-2.5 p-2.5': true,
        'text-gray-400 hover:text-gray-600': !hasNewNotifications,
        'text-red-400 hover:text-red-600': hasNewNotifications
    }">
    <BellIcon class="h-6 w-6" aria-hidden="true" />
    </Link>
</template>

<script setup>
import { BellIcon } from '@heroicons/vue/20/solid';
import { Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue';

const exceededWorkloads = computed(() => {
    return usePage().props.exceededWorkloads;
});

// Status apakah ada notifikasi baru
const hasNewNotifications = ref(false);

watch(
    () => exceededWorkloads.value,
    (newValue, oldValue) => {
        if (exceededWorkloads.value.length > 0) {
            hasNewNotifications.value = true;
        } else {
            hasNewNotifications.value = false;
        }
    },
    { immediate: true }
);

// Cek notifikasi baru pada saat komponen di-mount

// window.Echo.private('user.notification.' + usePage().props.auth.user.id)
//     .notification((notification) => {
//         hasNewNotifications.value = true;
//     });

</script>
