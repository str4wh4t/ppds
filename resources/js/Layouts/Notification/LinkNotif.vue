<template>
    <Link :href="route('notifs.index')" method="get" as="button" :class="{
        '-m-2.5 p-2.5': true,
        'text-gray-400 hover:text-gray-600': !hasUnreadConsultNotifications && !hasExceededWorkloadNotifications,
        'text-red-400 hover:text-red-600': hasUnreadConsultNotifications || hasExceededWorkloadNotifications
    }">
    <BellIcon class="h-6 w-6" aria-hidden="true" />
    </Link>
</template>

<script setup>
import { BellIcon } from '@heroicons/vue/20/solid';
import { Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue';

// Status apakah ada notifikasi baru
const hasExceededWorkloadNotifications = ref(false);

const exceededWorkloads = computed(() => {
    return usePage().props.exceededWorkloads;
});

const kaprodiUnits = computed(() => {
    return usePage().props.auth.user.kaprodi_units;
});

const ownedExceededWorkloads = exceededWorkloads.value?.filter(workload =>
    kaprodiUnits.value.some(unit => unit.id === workload.user.student_unit_id)
) ?? [];

watch(
    () => exceededWorkloads.value,
    (newValue, oldValue) => {
        if (exceededWorkloads.value != null) {
            if (exceededWorkloads.value.length > 0) {
                ownedExceededWorkloads.length > 0
                    ? hasExceededWorkloadNotifications.value = true
                    : hasExceededWorkloadNotifications.value = false;
                hasExceededWorkloadNotifications.value = true;
            } else {
                hasExceededWorkloadNotifications.value = false;
            }
        }
    },
    { immediate: true }
);

const hasUnreadConsultNotifications = ref(false);

const unreadConsults = computed(() => {
    return usePage().props.unreadConsults;
});

const dosbingStudents = computed(() => {
    return usePage().props.auth.user.dosbing_students;
});

const ownedUnreadConsults = unreadConsults.value?.filter(consult =>
    dosbingStudents.value.some(student => student.id === consult.user_id)
);

watch(
    () => unreadConsults.value,
    (newValue, oldValue) => {
        if (unreadConsults.value != null) {
            if (unreadConsults.value.length > 0) {
                ownedUnreadConsults.length > 0
                    ? hasUnreadConsultNotifications.value = true
                    : hasUnreadConsultNotifications.value = false;
            } else {
                hasUnreadConsultNotifications.value = false;
            }
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
