<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>

    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            Profile
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <div class="bg-white">
                <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" class="max-w-xl" />
            </div>
            <hr>
            <div class="bg-white">
                <UpdatePasswordForm class="max-w-xl" />
            </div>
            <!-- <hr>
            <div class="bg-white">
                <DeleteUserForm class="max-w-xl" />
            </div> -->
        </div>
    </AuthenticatedLayout>
</template>
